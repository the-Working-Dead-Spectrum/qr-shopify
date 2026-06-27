<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Contracts\ShopifyServiceInterface;
use App\Exceptions\Service\InvalidPayloadException;
use App\Exceptions\Shopify\InvalidWebhookException;
use App\Http\Controllers\Controller;
use App\Models\ShopifyWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Controller des webhooks Shopify entrants.
 *
 * Rôle strict :
 *  - Réceptionner la requête (déjà validée HMAC + replay par le middleware)
 *  - Déléguer 100% au ShopifyService
 *  - Répondre 202 Accepted IMMÉDIATEMENT (Shopify attend < 5s)
 *  - Tracker les métriques de traitement (latence, status)
 *
 * 7 endpoints supportés (cf. config/shopify.php) :
 *  - orders/create
 *  - orders/paid
 *  - orders/updated
 *  - orders/cancelled
 *  - orders/delete
 *  - refunds/create
 *  - app/uninstalled
 *
 * Aucune logique métier ici. Toute erreur technique est catchée et loggée
 * : on retourne TOUJOURS 202 à Shopify pour éviter les retry-loop inutiles
 * (la notification d'erreur admin est faite via le système de logs + Sentry).
 */
final class ShopifyWebhookController extends Controller
{
    public function __construct(
        private readonly ShopifyServiceInterface $shopifyService,
    ) {}

    // -------------------------------------------------------------------------
    // orders/create
    // -------------------------------------------------------------------------

    public function handleOrderCreated(Request $request): JsonResponse
    {
        return $this->process($request, 'orders/create', function (array $payload): void {
            $this->shopifyService->processOrderCreated($payload);
        });
    }

    // -------------------------------------------------------------------------
    // orders/paid
    // -------------------------------------------------------------------------

    public function handleOrderPaid(Request $request): JsonResponse
    {
        return $this->process($request, 'orders/paid', function (array $payload): void {
            $this->shopifyService->processOrderPaid($payload);
        });
    }

    // -------------------------------------------------------------------------
    // orders/updated
    // -------------------------------------------------------------------------

    public function handleOrderUpdated(Request $request): JsonResponse
    {
        return $this->process($request, 'orders/updated', function (array $payload): void {
            $this->shopifyService->processOrderUpdated($payload);
        });
    }

    // -------------------------------------------------------------------------
    // orders/cancelled
    // -------------------------------------------------------------------------

    public function handleOrderCancelled(Request $request): JsonResponse
    {
        return $this->process($request, 'orders/cancelled', function (array $payload): void {
            $this->shopifyService->processOrderCancelled($payload);
        });
    }

    // -------------------------------------------------------------------------
    // orders/delete
    // -------------------------------------------------------------------------

    public function handleOrderDeleted(Request $request): JsonResponse
    {
        return $this->process($request, 'orders/delete', function (array $payload): void {
            $this->shopifyService->processOrderDeleted($payload);
        });
    }

    // -------------------------------------------------------------------------
    // refunds/create
    // -------------------------------------------------------------------------

    public function handleRefundCreated(Request $request): JsonResponse
    {
        return $this->process($request, 'refunds/create', function (array $payload): void {
            $this->shopifyService->processRefundCreated($payload);
        });
    }

    // -------------------------------------------------------------------------
    // app/uninstalled
    // -------------------------------------------------------------------------

    public function handleAppUninstalled(Request $request): JsonResponse
    {
        return $this->process($request, 'app/uninstalled', function (array $payload): void {
            $this->shopifyService->processAppUninstalled($payload);
        });
    }

    // -------------------------------------------------------------------------
    // Implémentation centrale — mutualisée pour tous les webhooks
    // -------------------------------------------------------------------------

    /**
     * Pipeline commun : extraction payload → exécution → tracking → réponse.
     *
     * @param  callable(array<string, mixed>): void  $handler
     */
    private function process(Request $request, string $topic, callable $handler): JsonResponse
    {
        $start = microtime(true);

        $payload = $this->extractPayload($request);

        if ($payload === null) {
            return $this->accepted();
        }

        /** @var ShopifyWebhookEvent|null $event */
        $event = $request->attributes->get('shopify.event');

        try {
            $handler($payload);

            if ($event instanceof ShopifyWebhookEvent) {
                $event->markProcessed($payload['id'] ?? null);
            }

            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            Log::info("[webhook] {$topic}_processed", [
                'topic' => $topic,
                'latency_ms' => $latencyMs,
            ]);

        } catch (InvalidWebhookException|InvalidPayloadException $e) {
            if ($event instanceof ShopifyWebhookEvent) {
                $event->markFailed($e->getMessage());
            }

            Log::warning("[webhook] {$topic}_invalid_payload", [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            // Payload définitivement invalide — pas de retry
            return response()->json(['error' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            if ($event instanceof ShopifyWebhookEvent) {
                $event->markFailed($e->getMessage());
            }

            Log::error("[webhook] {$topic}_failed", [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Erreur technique : on retourne 202 quand même.
            // Shopify ne doit PAS rejouer indéfiniment.
            // L'erreur est tracée pour traitement via NotifyAdminOnErrorJob / Sentry.
        }

        return $this->accepted();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Récupère le payload brut. Le middleware HMAC a déjà vérifié la signature,
     * on peut parser en confiance.
     *
     * @return array<string, mixed>|null
     */
    private function extractPayload(Request $request): ?array
    {
        try {
            $data = $request->all();

            if (! is_array($data) || $data === []) {
                // Fallback : essayer json() si all() a échoué (ex: test HTTP)
                if ($request->isJson()) {
                    $data = $request->json()->all();
                }
            }

            if (! is_array($data)) {
                Log::error('[webhook] payload_not_array', [
                    'type' => gettype($data),
                    'content_type' => $request->header('Content-Type'),
                ]);

                return null;
            }

            if ($data === []) {
                // Dernier recours : parser le body brut manuellement.
                $rawBody = $request->getContent();

                if ($rawBody !== '') {
                    $decoded = json_decode($rawBody, true);

                    if (is_array($decoded)) {
                        $data = $decoded;
                    }
                }
            }

            return $data;
        } catch (Throwable $e) {
            Log::error('[webhook] payload_parse_failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Réponse standard 202 Accepted.
     * Cf. SPECS §3.2 : webhook doit toujours répondre vite.
     */
    private function accepted(): JsonResponse
    {
        return response()->json(['status' => 'accepted'], 202);
    }
}
