<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Shopify\InvalidHmacException;
use App\Exceptions\Shopify\ShopifyConfigurationException;
use App\Exceptions\Shopify\WebhookReplayException;
use App\Models\ShopifyWebhookEvent;
use App\Services\Shopify\ShopifySecretRotationService;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Vérifie que chaque webhook entrant provient bien de Shopify.
 *
 * Trois sécurités empilées :
 *  1. Vérification HMAC-SHA256 (signature cryptographique)
 *  2. Protection replay via X-Shopify-Webhook-Id (table shopify_webhook_events)
 *  3. Vérification topic whitelist (config('shopify.webhook_topics'))
 *
 * Règles de sécurité critiques :
 * - Lire le body brut AVANT tout parsing (Laravel peut le modifier)
 * - Comparer avec hash_equals() — résistant aux timing attacks
 * - Ne jamais logguer le body brut ni le secret
 * - Toujours retourner 401 sans détail en cas d'échec HMAC
 *
 * Ce middleware ATTACHE aussi les informations du webhook à la requête
 * (attributs `shopify.*`) pour les controllers downstream :
 *   - shopify.webhook_id
 *   - shopify.topic
 *   - shopify.shop_domain
 *   - shopify.event (modèle ShopifyWebhookEvent persisté)
 */
class VerifyShopifyHmac
{
    public function __construct(
        private readonly ShopifySecretRotationService $secretRotation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // -----------------------------------------------------------------
        // 1. Extraction des headers
        // -----------------------------------------------------------------
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $webhookId = $request->header('X-Shopify-Webhook-Id');
        $topic = $request->header('X-Shopify-Topic');
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        // Header HMAC absent — rejet immédiat
        if (empty($hmacHeader)) {
            Log::warning('[shopify.webhook] rejected_missing_hmac', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ]);

            return response()->json(
                ['error' => 'Invalid HMAC signature'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        // Webhook ID absent — Shopify le fournit systématiquement
        if (empty($webhookId)) {
            Log::warning('[shopify.webhook] rejected_missing_webhook_id', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json(
                ['error' => 'Missing X-Shopify-Webhook-Id header'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        // -----------------------------------------------------------------
        // 2. Vérification HMAC
        // -----------------------------------------------------------------
        try {
            $this->verifyHmac($request, $hmacHeader);
        } catch (InvalidHmacException) {
            // Log minimal — jamais le HMAC reçu ni le body.
            Log::warning('[shopify.webhook] rejected_invalid_hmac', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'topic' => $topic,
                'webhook_id' => $webhookId,
            ]);

            return response()->json(
                ['error' => 'Invalid HMAC signature'],
                Response::HTTP_UNAUTHORIZED,
            );
        } catch (ShopifyConfigurationException) {
            return response()->json(
                ['error' => 'Server configuration error'],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        // -----------------------------------------------------------------
        // 3. Vérification topic (whitelist)
        // -----------------------------------------------------------------
        $allowedTopics = (array) config('shopify.webhook_topics', []);

        if (! empty($allowedTopics) && ! in_array($topic, $allowedTopics, true)) {
            Log::warning('[shopify.webhook] rejected_unknown_topic', [
                'topic' => $topic,
                'webhook_id' => $webhookId,
                'path' => $request->path(),
            ]);

            return response()->json(
                ['error' => 'Unsupported webhook topic'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // -----------------------------------------------------------------
        // 4. Protection replay (X-Shopify-Webhook-Id)
        // -----------------------------------------------------------------
        if ((bool) config('shopify.replay_protection.enabled', true)) {
            try {
                $event = $this->registerWebhookEvent(
                    $webhookId,
                    $topic,
                    $shopDomain,
                    $request,
                );
            } catch (WebhookReplayException) {
                Log::warning('[shopify.webhook] rejected_replay', [
                    'webhook_id' => $webhookId,
                    'topic' => $topic,
                    'shop' => $shopDomain,
                    'ip' => $request->ip(),
                ]);

                return response()->json(
                    ['error' => 'Webhook already processed'],
                    Response::HTTP_CONFLICT,
                );
            } catch (Throwable $e) {
                // Erreur DB inattendue — on log et on laisse passer
                // (HMAC est déjà validé, mieux vaut accepter que bloquer).
                Log::error('[shopify.webhook] replay_check_failed', [
                    'webhook_id' => $webhookId,
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
                $event = null;
            }

            // Attache les infos au request pour le controller
            if ($event !== null) {
                $request->attributes->set('shopify.webhook_id', $webhookId);
                $request->attributes->set('shopify.event', $event);
            }
        }

        // -----------------------------------------------------------------
        // 5. Attache les headers à la requête pour usage downstream
        // -----------------------------------------------------------------
        $request->attributes->set('shopify.topic', $topic);
        $request->attributes->set('shopify.shop_domain', $shopDomain);

        return $next($request);
    }

    // -------------------------------------------------------------------------
    // Vérification HMAC
    // -------------------------------------------------------------------------

    /**
     * Vérifie la signature HMAC du body brut.
     *
     * Stratégie de rotation gracieuse : si plusieurs secrets sont valides
     * (pendant une rotation), on accepte si l'un d'eux correspond.
     *
     * @throws InvalidHmacException
     * @throws ShopifyConfigurationException
     */
    private function verifyHmac(Request $request, string $hmacHeader): void
    {
        $secrets = $this->secretRotation->getAllValidSecrets();

        if ($secrets === []) {
            Log::error('[shopify.config] webhook_secret_missing', [
                'path' => $request->path(),
            ]);

            throw new ShopifyConfigurationException(
                'SHOPIFY_WEBHOOK_SECRET non configuré',
                'shopify.webhook_secret',
            );
        }

        // Lecture du body brut — OBLIGATOIRE avant tout parsing JSON
        // $request->all() ou $request->json() peuvent altérer le body
        $rawBody = $request->getContent();

        $matched = false;

        foreach ($secrets as $secret) {
            $computedHmac = base64_encode(
                hash_hmac('sha256', $rawBody, $secret, binary: true),
            );

            // Comparaison en temps constant — hash_equals() protège contre les timing attacks
            // NE PAS utiliser === ici, même pour un caractère différent
            if (hash_equals($computedHmac, $hmacHeader)) {
                $matched = true;
                break;
            }
        }

        if (! $matched) {
            throw new InvalidHmacException('HMAC signature mismatch');
        }
    }

    // -------------------------------------------------------------------------
    // Protection replay
    // -------------------------------------------------------------------------

    /**
     * Tente d'enregistrer l'événement webhook. Si l'ID existe déjà → replay.
     *
     * @throws WebhookReplayException Si le webhook_id a déjà été traité.
     */
    private function registerWebhookEvent(
        string $webhookId,
        ?string $topic,
        ?string $shopDomain,
        Request $request,
    ): ShopifyWebhookEvent {
        $rawBody = $request->getContent();
        $payloadHash = $rawBody !== '' ? hash('sha256', $rawBody) : null;

        try {
            return ShopifyWebhookEvent::create([
                'webhook_id' => $webhookId,
                'topic' => $topic ?? 'unknown',
                'shop_domain' => $shopDomain,
                'payload_hash' => $payloadHash,
                'status' => 'received',
                'received_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Duplicate key → webhook déjà traité
            if ($this->isDuplicateKeyError($e)) {
                throw new WebhookReplayException(
                    webhookId: $webhookId,
                    topic: $topic ?? 'unknown',
                );
            }

            throw $e;
        }
    }

    /**
     * Détecte une violation UNIQUE constraint MySQL.
     */
    private function isDuplicateKeyError(QueryException $e): bool
    {
        // SQLSTATE 23000 = integrity constraint violation
        // Code MySQL 1062 = duplicate entry
        return ($e->errorInfo[0] ?? null) === '23000'
            && in_array((int) ($e->errorInfo[1] ?? 0), [1062, 1586], true);
    }
}
