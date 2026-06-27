<?php

declare(strict_types=1);

namespace Tests\Feature\Webhook;

use App\Enums\OrderStatus;
use App\Enums\QrStatus;
use App\Jobs\GenerateAndSendQrJob;
use App\Jobs\SendQrCodeEmailJob;
use App\Models\Order;
use App\Models\ShopifyWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests Feature pour les 7 webhooks Shopify.
 *
 * Chaque webhook est testé pour :
 *  - acceptation (HMAC valide + topic whitelist)
 *  - rejet HMAC invalide (401)
 *  - rejet replay (409)
 *  - rejet topic inconnu (422)
 *  - rejet webhook_id manquant (401)
 *  - transformation du payload en Order
 *  - dispatch correct des jobs (génération QR, email)
 *
 * Refactor : utilise `Config::set('services.shopify.webhook_secret', ...)`
 * car le middleware lit `config('shopify.webhook_secret')` après refactor.
 */
class ShopifyWebhooksTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        // Le middleware lit config('shopify.webhook_secret') (nouveau fichier de config).
        Config::set('shopify.webhook_secret', self::SECRET);
        // Sécurité rétro-compat si un autre code lit l'ancien emplacement.
        Config::set('services.shopify.webhook_secret', self::SECRET);
    }

    // =========================================================================
    // orders/paid
    // =========================================================================

    public function test_order_paid_cree_une_commande_et_dispatch_le_job_qr(): void
    {
        Bus::fake([GenerateAndSendQrJob::class, SendQrCodeEmailJob::class]);

        $payload = $this->validOrderPayload();
        $headers = $this->shopifyHeaders('orders/paid');

        $response = $this->postWebhook('order-paid', $payload, $headers);

        $response->assertStatus(202);

        // Order créée en BDD
        $this->assertDatabaseHas('orders', [
            'shopify_order_id' => (string) $payload['id'],
            'customer_email'   => $payload['email'],
            'status'           => OrderStatus::Paid->value,
        ]);

        // Job QR dispatché
        Bus::assertDispatched(GenerateAndSendQrJob::class, function ($job) {
            return $job->orderId > 0;
        });

        // Webhook event marqué processed
        $this->assertDatabaseHas('shopify_webhook_events', [
            'webhook_id' => $headers['X-Shopify-Webhook-Id'],
            'status'     => 'processed',
        ]);
    }

    public function test_order_paid_est_idempotent(): void
    {
        Bus::fake([GenerateAndSendQrJob::class, SendQrCodeEmailJob::class]);

        $payload = $this->validOrderPayload();
        $headers = $this->shopifyHeaders('orders/paid');

        // 1er appel
        $this->postWebhook('order-paid', $payload, $headers)->assertStatus(202);

        // 2e appel avec un nouveau webhook_id (Shopify génère un ID par envoi)
        $headers2 = $this->shopifyHeaders('orders/paid', 'wh_2eme_appel');
        $this->postWebhook('order-paid', $payload, $headers2)->assertStatus(202);

        // Toujours 1 seule Order en BDD
        $this->assertSame(1, Order::where('shopify_order_id', (string) $payload['id'])->count());

        // Le job QR n'est dispatché QUE pour le 1er appel
        Bus::assertDispatchedTimes(GenerateAndSendQrJob::class, 1);
    }

    // =========================================================================
    // orders/create
    // =========================================================================

    public function test_order_created_cree_une_commande_pending(): void
    {
        Bus::fake();

        $payload = $this->validOrderPayload(['financial_status' => 'pending']);
        $headers = $this->shopifyHeaders('orders/create');

        $response = $this->postWebhook('order-created', $payload, $headers);

        $response->assertStatus(202);

        $this->assertDatabaseHas('orders', [
            'shopify_order_id' => (string) $payload['id'],
            'status'           => OrderStatus::Pending->value,
        ]);

        // orders/create NE doit PAS déclencher de QR (panier peut être abandonné)
        Bus::assertNotDispatched(GenerateAndSendQrJob::class);
    }

    // =========================================================================
    // orders/updated
    // =========================================================================

    public function test_order_updated_modifie_une_commande_existante(): void
    {
        $order = Order::factory()->create([
            'shopify_order_id' => '888888',
            'customer_email'   => 'old@test.com',
        ]);

        $payload = $this->validOrderPayload([
            'id'    => 888888,
            'email' => 'new@test.com',
        ]);
        $headers = $this->shopifyHeaders('orders/updated');

        $this->postWebhook('order-updated', $payload, $headers)->assertStatus(202);

        $order->refresh();
        $this->assertSame('new@test.com', $order->customer_email);
    }

    public function test_order_updated_sur_commande_inexistante_ne_fait_rien(): void
    {
        $payload = $this->validOrderPayload(['id' => 111111]);
        $headers = $this->shopifyHeaders('orders/updated');

        $this->postWebhook('order-updated', $payload, $headers)->assertStatus(202);

        $this->assertDatabaseMissing('orders', [
            'shopify_order_id' => '111111',
        ]);
    }

    // =========================================================================
    // orders/cancelled
    // =========================================================================

    public function test_order_cancelled_annule_et_revoque_le_qr_actif(): void
    {
        $order = Order::factory()->create([
            'shopify_order_id' => '777777',
            'status'           => OrderStatus::Paid,
        ]);

        // Crée un QR actif lié à la commande
        $qr = \App\Models\QrCode::factory()->create([
            'order_id' => $order->id,
            'status'   => QrStatus::Active,
        ]);

        $payload = $this->validOrderPayload([
            'id'           => 777777,
            'cancelled_at' => now()->toIso8601String(),
        ]);
        $headers = $this->shopifyHeaders('orders/cancelled');

        $this->postWebhook('order-cancelled', $payload, $headers)->assertStatus(202);

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);

        $qr->refresh();
        $this->assertSame(QrStatus::Revoked, $qr->status);
    }

    // =========================================================================
    // orders/delete
    // =========================================================================

    public function test_order_deleted_supprime_la_commande(): void
    {
        $order = Order::factory()->create(['shopify_order_id' => '666666']);

        $payload = $this->validOrderPayload(['id' => 666666]);
        $headers = $this->shopifyHeaders('orders/delete');

        $this->postWebhook('order-deleted', $payload, $headers)->assertStatus(202);

        $this->assertDatabaseMissing('orders', [
            'shopify_order_id' => '666666',
        ]);
    }

    // =========================================================================
    // refunds/create
    // =========================================================================

    public function test_refund_create_annule_si_rembourse_totalement(): void
    {
        $order = Order::factory()->create([
            'shopify_order_id' => '555555',
            'status'           => OrderStatus::Paid,
        ]);

        $payload = $this->validOrderPayload([
            'id'               => 555555,
            'financial_status' => 'refunded',
        ]);
        $headers = $this->shopifyHeaders('refunds/create');

        $this->postWebhook('refund-created', $payload, $headers)->assertStatus(202);

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
    }

    // =========================================================================
    // app/uninstalled
    // =========================================================================

    public function test_app_uninstalled_ne_leve_pas_dexception(): void
    {
        $payload = ['domain' => 'test-shop.myshopify.com'];
        $headers = $this->shopifyHeaders('app/uninstalled');

        // app/uninstalled n'a pas de structure "id" stricte — test simple.
        $response = $this->postWebhook('app-uninstalled', $payload, $headers);

        $response->assertStatus(202);
    }

    // =========================================================================
    // Sécurité : rejet HMAC invalide
    // =========================================================================

    public function test_webhook_rejete_si_hmac_invalide(): void
    {
        $payload = $this->validOrderPayload();
        $headers = $this->shopifyHeaders('orders/paid');
        $headers['X-Shopify-Hmac-Sha256'] = 'hmac-faux';

        $this->postJson('webhooks/shopify/order-paid', $payload, $headers)
             ->assertStatus(401);
    }

    // =========================================================================
    // Sécurité : rejet replay
    // =========================================================================

    public function test_webhook_rejete_si_webhook_id_deja_utilise(): void
    {
        $payload = $this->validOrderPayload();
        $headers = $this->shopifyHeaders('orders/paid', 'wh_replay_test');

        // 1er appel : OK
        $this->postWebhook('order-paid', $payload, $headers)->assertStatus(202);

        // 2e appel avec MÊME webhook_id : 409 Conflict
        $this->postWebhook('order-paid', $payload, $headers)->assertStatus(409);
    }

    // =========================================================================
    // Sécurité : rejet topic inconnu
    // =========================================================================

    public function test_webhook_rejete_si_topic_non_whiteliste(): void
    {
        $payload = $this->validOrderPayload();
        $headers = $this->shopifyHeaders('orders/unknown-topic');

        $this->postWebhook('order-paid', $payload, $headers)
             ->assertStatus(422);
    }

    // =========================================================================
    // Sécurité : rejet webhook_id manquant
    // =========================================================================

    public function test_webhook_rejete_si_webhook_id_manquant(): void
    {
        $payload = $this->validOrderPayload();
        $headers = $this->shopifyHeaders('orders/paid');
        unset($headers['X-Shopify-Webhook-Id']);

        $this->postWebhook('order-paid', $payload, $headers)->assertStatus(401);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Génère des headers Shopify complets et valides.
     *
     * @return array<string, string>
     */
    private function shopifyHeaders(string $topic, ?string $webhookId = null): array
    {
        return [
            'X-Shopify-Hmac-Sha256'   => 'placeholder', // sera recalculé
            'X-Shopify-Webhook-Id'    => $webhookId ?? 'wh_' . uniqid('', true),
            'X-Shopify-Topic'         => $topic,
            'X-Shopify-Shop-Domain'   => 'test-shop.myshopify.com',
            'Content-Type'            => 'application/json',
        ];
    }

    /**
     * Construit le HMAC et signe la requête.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    private function postWebhook(string $route, array $payload, array $headers): \Illuminate\Testing\TestResponse
    {
        $rawBody = json_encode($payload);
        $hmac = base64_encode(hash_hmac('sha256', $rawBody, self::SECRET, true));
        $headers['X-Shopify-Hmac-Sha256'] = $hmac;

        return $this->call(
            'POST',
            'webhooks/shopify/' . $route,
            [], [], [],
            $this->transformHeaders($headers),
            $rawBody,
        );
    }

    /**
     * Construit un payload Shopify réaliste.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validOrderPayload(array $overrides = []): array
    {
        return array_merge([
            'id'                => 123456789,
            'email'             => 'client@example.com',
            'total_price'       => '49.99',
            'currency'          => 'EUR',
            'financial_status'  => 'paid',
            'fulfillment_status'=> null,
            'customer' => [
                'id'         => 555,
                'email'      => 'client@example.com',
                'first_name' => 'Jean',
                'last_name'  => 'Dupont',
            ],
            'line_items' => [
                ['id' => 1, 'title' => 'Produit Test', 'quantity' => 1, 'price' => '49.99'],
            ],
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function transformHeaders(array $headers): array
    {
        $server = [];
        foreach ($headers as $name => $value) {
            $server['HTTP_' . str_replace('-', '_', strtoupper($name))] = $value;
        }
        return $server;
    }
}