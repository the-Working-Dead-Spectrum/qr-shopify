<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\ShopifyServiceInterface;
use App\Enums\OrderStatus;
use App\Enums\QrStatus;
use App\Exceptions\Service\InvalidPayloadException;
use App\Jobs\GenerateAndSendQrJob;
use App\Models\Order;
use App\Models\QrCode;
use App\Services\ShopifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Tests unitaires du ShopifyService.
 *
 * Couvre l'invariant d'idempotence (SPECS §5.2) :
 *  - plusieurs webhooks `orders/paid` pour la même commande = 1 seule Order
 *  - un seul job de génération dispatché (premier traitement uniquement)
 *  - rejouer manuellement le service ne crée PAS de doublon
 *
 * Et l'invariant d'annulation :
 *  - orders/cancelled met à jour le statut Order ET révoque le QR actif.
 */
final class ShopifyServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShopifyServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShopifyService();
    }

    // -------------------------------------------------------------------------
    // processOrderPaid — création idempotente
    // -------------------------------------------------------------------------

    public function test_process_order_paid_creates_order(): void
    {
        $payload = $this->validPayload();

        $order = $this->service->processOrderPaid($payload);

        $this->assertSame('1234567890', $order->shopify_order_id);
        $this->assertSame('client@example.com', $order->customer_email);
        $this->assertSame('Marie Dupont', $order->customer_name);
        $this->assertSame(4999, $order->amount_cents);
        $this->assertSame('EUR', $order->currency);
        $this->assertSame(OrderStatus::Paid, $order->status);
    }

    public function test_process_order_paid_dispatches_generation_job_on_first_call(): void
    {
        Bus::fake([GenerateAndSendQrJob::class]);
        $payload = $this->validPayload();

        $order = $this->service->processOrderPaid($payload);

        // Le dispatch est différé via DB::afterCommit. RefreshDatabase enveloppe le
        // test dans une transaction qui ne commit jamais, donc on force le commit
        // pour exécuter les callbacks afterCommit. On nettoie ensuite la DB créée.
        $this->commitRefreshDatabaseTransaction();

        Bus::assertDispatched(GenerateAndSendQrJob::class, function ($job) use ($order): bool {
            return $job->orderId === $order->id;
        });

        // Nettoyage des données créées par le commit (RefreshDatabase ne pourra pas les rollback).
        Order::query()->where('shopify_order_id', '1234567890')->delete();
    }

    public function test_process_order_paid_is_idempotent_for_same_shopify_id(): void
    {
        $payload = $this->validPayload();

        $first = $this->service->processOrderPaid($payload);
        $second = $this->service->processOrderPaid($payload);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Order::where('shopify_order_id', '1234567890')->count());
    }

    public function test_process_order_paid_does_not_re_dispatch_job_on_replay(): void
    {
        Bus::fake([GenerateAndSendQrJob::class]);
        $payload = $this->validPayload();

        // Premier appel → aprèsCommit enregistré
        $this->service->processOrderPaid($payload);
        // Deuxième appel (même payload) → pas d'enregistrement (idempotence)
        $this->service->processOrderPaid($payload);

        $this->commitRefreshDatabaseTransaction();

        Bus::assertDispatchedTimes(GenerateAndSendQrJob::class, 1);

        Order::query()->where('shopify_order_id', '1234567890')->delete();
    }

    public function test_process_order_paid_uses_was_recently_created(): void
    {
        $payload = $this->validPayload();

        $first = $this->service->processOrderPaid($payload);

        // wasRecentlyCreated est un flag en mémoire, positionné par firstOrCreate.
        // Il est reset dès que le modèle est réhydraté.
        $this->assertTrue($first->wasRecentlyCreated);

        // Le 2e appel lit l'Order existant : wasRecentlyCreated est false.
        $second = $this->service->processOrderPaid($payload);
        $this->assertFalse($second->wasRecentlyCreated);
    }

    /**
     * Commit la transaction ouverte par RefreshDatabase.
     *
     * Déclenche tous les callbacks afterCommit en attente, comme en production
     * à la fin du request lifecycle. À utiliser uniquement pour tester le
     * dispatch différé. ⚠️ Fait un vrai COMMIT, donc l'appelant doit nettoyer
     * manuellement les données créées (Order::query()->delete()).
     */
    private function commitRefreshDatabaseTransaction(): void
    {
        $db = $this->app['db'];
        $connection = $db->connection();
        $refl = new \ReflectionObject($connection);

        if (! $refl->hasProperty('transactionsManager')) {
            return;
        }

        $prop = $refl->getProperty('transactionsManager');
        $prop->setAccessible(true);
        $manager = $prop->getValue($connection);

        if ($manager === null) {
            return;
        }

        $transactionLevel = $connection->transactionLevel();

        // Appelle commit() pour exécuter les callbacks afterCommit.
        $manager->commit(
            $connection->getName(),
            $transactionLevel,
            $transactionLevel - 1
        );
    }

    public function test_process_order_paid_accepts_integer_id(): void
    {
        $payload = $this->validPayload(['id' => 99999]);

        $order = $this->service->processOrderPaid($payload);

        $this->assertSame('99999', $order->shopify_order_id);
    }

    public function test_process_order_paid_handles_missing_customer_name(): void
    {
        $payload = $this->validPayload();
        unset($payload['customer']);

        $order = $this->service->processOrderPaid($payload);

        $this->assertNull($order->customer_name);
    }

    // -------------------------------------------------------------------------
    // processOrderPaid — erreurs
    // -------------------------------------------------------------------------

    public function test_process_order_paid_throws_on_missing_email(): void
    {
        $payload = $this->validPayload();
        unset($payload['email']);

        $this->expectException(InvalidPayloadException::class);
        $this->service->processOrderPaid($payload);
    }

    public function test_process_order_paid_throws_on_missing_id(): void
    {
        $payload = $this->validPayload();
        unset($payload['id']);

        $this->expectException(InvalidPayloadException::class);
        $this->service->processOrderPaid($payload);
    }

    public function test_process_order_paid_rolls_back_on_failure(): void
    {
        // Forcer une erreur DB : un payload invalide doit lever une exception
        // et la transaction doit faire un rollback complet (0 Order créée).
        $payload = $this->validPayload();
        unset($payload['email']);

        try {
            $this->service->processOrderPaid($payload);
            $this->fail('Exception attendue');
        } catch (InvalidPayloadException) {
            // OK
        }

        // Aucune Order ne doit avoir été créée suite à l'échec.
        $this->assertSame(0, Order::count());
    }

    // -------------------------------------------------------------------------
    // processOrderCancelled — annulation
    // -------------------------------------------------------------------------

    public function test_process_order_cancelled_returns_null_for_unknown_order(): void
    {
        $payload = $this->validPayload(['cancelled_at' => '2026-01-15T10:00:00Z']);

        $result = $this->service->processOrderCancelled($payload);

        $this->assertNull($result);
    }

    public function test_process_order_cancelled_marks_order_as_cancelled(): void
    {
        $order = Order::factory()->paid()->create(['shopify_order_id' => '111']);
        $payload = $this->validPayload([
            'id'           => '111',
            'cancelled_at' => '2026-01-15T10:00:00Z',
        ]);

        $result = $this->service->processOrderCancelled($payload);

        $this->assertNotNull($result);
        $this->assertSame(OrderStatus::Cancelled, $result->status);
        $this->assertSame($order->id, $result->id);
    }

    public function test_process_order_cancelled_revokes_active_qr(): void
    {
        $order = Order::factory()->paid()->create(['shopify_order_id' => '222']);
        $qr = QrCode::factory()->active()->create(['order_id' => $order->id]);

        $payload = $this->validPayload([
            'id'           => '222',
            'cancelled_at' => '2026-01-15T10:00:00Z',
        ]);

        $this->service->processOrderCancelled($payload);

        $qr->refresh();
        $this->assertSame(QrStatus::Revoked, $qr->status);
    }

    public function test_process_order_cancelled_does_not_touch_already_revoked_qr(): void
    {
        $order = Order::factory()->paid()->create(['shopify_order_id' => '333']);
        $qr = QrCode::factory()->revoked()->create(['order_id' => $order->id]);

        $payload = $this->validPayload([
            'id'           => '333',
            'cancelled_at' => '2026-01-15T10:00:00Z',
        ]);

        $this->service->processOrderCancelled($payload);

        $qr->refresh();
        $this->assertSame(QrStatus::Revoked, $qr->status);
    }

    public function test_process_order_cancelled_keeps_used_qr_unchanged(): void
    {
        // Un QR déjà utilisé avant l'annulation reste "used" (historique).
        $order = Order::factory()->paid()->create(['shopify_order_id' => '444']);
        $qr = QrCode::factory()->used()->create(['order_id' => $order->id]);

        $payload = $this->validPayload([
            'id'           => '444',
            'cancelled_at' => '2026-01-15T10:00:00Z',
        ]);

        $this->service->processOrderCancelled($payload);

        $qr->refresh();
        $this->assertSame(QrStatus::Used, $qr->status);
        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }

    public function test_process_order_cancelled_does_not_revoke_if_no_qr(): void
    {
        // Order sans QR (cas : webhoook annulation sans paiement préalable)
        Order::factory()->paid()->create(['shopify_order_id' => '555']);

        $payload = $this->validPayload([
            'id'           => '555',
            'cancelled_at' => '2026-01-15T10:00:00Z',
        ]);

        // Ne doit pas crasher.
        $result = $this->service->processOrderCancelled($payload);

        $this->assertNotNull($result);
        $this->assertSame(OrderStatus::Cancelled, $result->status);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'id'           => '1234567890',
            'email'        => 'client@example.com',
            'total_price'  => '49.99',
            'currency'     => 'EUR',
            'customer'     => [
                'first_name' => 'Marie',
                'last_name'  => 'Dupont',
            ],
        ], $overrides);
    }
}
