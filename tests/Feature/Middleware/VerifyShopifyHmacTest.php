<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class VerifyShopifyHmacTest extends TestCase
{
    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        // Le middleware lit config('shopify.webhook_secret') (nouveau fichier config).
        Config::set('shopify.webhook_secret', self::SECRET);
        // Rétro-compat si le code lit encore l'ancien emplacement.
        Config::set('services.shopify.webhook_secret', self::SECRET);

        // La protection replay est désactivée pour les tests isolés de ce middleware.
        Config::set('shopify.replay_protection.enabled', false);

        // Route de test enregistrée uniquement pour cette suite
        $this->app['router']->post('webhooks/test', fn () => response()->json(['ok' => true]))
             ->middleware('shopify.hmac');
    }

    // -------------------------------------------------------------------------
    // Cas d'acceptation
    // -------------------------------------------------------------------------

    public function test_accepte_un_webhook_avec_hmac_valide(): void
    {
        $body = json_encode(['id' => 123, 'email' => 'client@test.com']);
        $hmac = $this->computeHmac($body);

        $response = $this->withHeaders($this->shopifyHeaders($hmac))
                         ->postJson('webhooks/test', json_decode($body, true));

        $response->assertStatus(200)
                 ->assertJson(['ok' => true]);
    }

    public function test_accepte_un_webhook_rejoue_avec_le_meme_hmac(): void
    {
        $body = json_encode(['id' => 456]);
        $hmac = $this->computeHmac($body);

        // Premier appel
        $this->withHeaders($this->shopifyHeaders($hmac))
             ->postJson('webhooks/test', json_decode($body, true))
             ->assertStatus(200);

        // Deuxième appel — le middleware ne bloque pas la répétition (c'est le Service qui gère l'idempotence)
        $this->withHeaders($this->shopifyHeaders($hmac))
             ->postJson('webhooks/test', json_decode($body, true))
             ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Cas de rejet
    // -------------------------------------------------------------------------

    public function test_rejette_sans_header_hmac(): void
    {
        $this->withHeaders(['X-Shopify-Webhook-Id' => 'wh-1'])
             ->postJson('webhooks/test', ['id' => 123])
             ->assertStatus(401)
             ->assertJson(['error' => 'Invalid HMAC signature']);
    }

    public function test_rejette_avec_hmac_incorrect(): void
    {
        $this->withHeaders([
            'X-Shopify-Hmac-Sha256'  => 'hmac-totalement-faux',
            'X-Shopify-Webhook-Id'   => 'wh-2',
        ])
             ->postJson('webhooks/test', ['id' => 123])
             ->assertStatus(401)
             ->assertJson(['error' => 'Invalid HMAC signature']);
    }

    public function test_rejette_avec_hmac_dun_autre_secret(): void
    {
        $body = json_encode(['id' => 789]);
        // Signé avec un secret différent
        $wrongHmac = base64_encode(hash_hmac('sha256', $body, 'mauvais-secret', true));

        $this->withHeaders([
            'X-Shopify-Hmac-Sha256' => $wrongHmac,
            'X-Shopify-Webhook-Id'  => 'wh-3',
        ])
             ->postJson('webhooks/test', json_decode($body, true))
             ->assertStatus(401);
    }

    public function test_rejette_si_secret_non_configure(): void
    {
        Config::set('shopify.webhook_secret', '');
        Config::set('services.shopify.webhook_secret', '');

        $body = json_encode(['id' => 999]);
        $hmac = $this->computeHmac($body);

        $this->withHeaders([
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Webhook-Id'  => 'wh-4',
        ])
             ->postJson('webhooks/test', json_decode($body, true))
             ->assertStatus(500);
    }

    public function test_rejette_avec_header_vide(): void
    {
        $this->withHeaders([
            'X-Shopify-Hmac-Sha256' => '',
            'X-Shopify-Webhook-Id'  => 'wh-5',
        ])
             ->postJson('webhooks/test', ['id' => 123])
             ->assertStatus(401);
    }

    public function test_rejette_sans_webhook_id(): void
    {
        $body = json_encode(['id' => 123]);
        $hmac = $this->computeHmac($body);

        $this->withHeaders(['X-Shopify-Hmac-Sha256' => $hmac])
             ->postJson('webhooks/test', json_decode($body, true))
             ->assertStatus(401)
             ->assertJson(['error' => 'Missing X-Shopify-Webhook-Id header']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function computeHmac(string $rawBody): string
    {
        return base64_encode(hash_hmac('sha256', $rawBody, self::SECRET, true));
    }

    /**
     * @return array<string, string>
     */
    private function shopifyHeaders(string $hmac): array
    {
        return [
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Webhook-Id'  => 'wh-' . uniqid('', true),
            'X-Shopify-Topic'       => 'orders/paid',
            'X-Shopify-Shop-Domain' => 'test-shop.myshopify.com',
        ];
    }
}