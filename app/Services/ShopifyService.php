<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ShopifyServiceInterface;
use App\Enums\OrderStatus;
use App\Enums\QrStatus;
use App\Events\Shopify\AppUninstalled;
use App\Events\Shopify\OrderCancelled;
use App\Events\Shopify\OrderImported;
use App\Events\Shopify\OrderPaid;
use App\Events\Shopify\OrderUpdated;
use App\Exceptions\Service\InvalidPayloadException;
use App\Exceptions\Shopify\InvalidWebhookException;
use App\Jobs\GenerateAndSendQrJob;
use App\Models\Order;
use App\Services\Concerns\LogsServiceActivity;
use App\Services\Support\OrderPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service de traitement des webhooks Shopify.
 *
 * Responsabilités :
 *  - Normaliser le payload Shopify via OrderPayload
 *  - Garantir l'idempotence : UN même webhook N fois = 1 seule mutation
 *  - Dispatcher le job de génération QR uniquement à la première création
 *  - Gérer l'annulation : update Order + révocation QR actif
 *  - Émettre les événements Laravel (OrderImported, OrderPaid, etc.)
 *
 * Critère d'idempotence :
 *   UNIQUE(shopify_order_id) au niveau MySQL + firstOrCreate applicatif
 *   → double protection contre la duplication.
 *
 * Stratégie d'événements :
 *   On émet APRÈS le commit DB (via DB::afterCommit) pour garantir
 *   que les listeners ont accès à des données persistées.
 */
final class ShopifyService implements ShopifyServiceInterface
{
    use LogsServiceActivity;

    // -------------------------------------------------------------------------
    // orders/create
    // -------------------------------------------------------------------------

    public function processOrderCreated(array $payload): Order
    {
        $dto = $this->buildPayload($payload);

        try {
            return DB::transaction(function () use ($dto): Order {
                /** @var Order $order */
                $order = Order::firstOrCreate(
                    ['shopify_order_id' => $dto->shopifyOrderId],
                    [
                        'customer_email' => $dto->customerEmail,
                        'customer_name' => $dto->customerName,
                        'amount_cents' => $dto->amountCents,
                        'currency' => $dto->currency,
                        'status' => $dto->toOrderStatus(),
                    ],
                );

                if ($order->wasRecentlyCreated) {
                    $this->info('shopify.order_created.imported', [
                        'order_id' => $order->id,
                        'shopify_order_id' => $order->shopify_order_id,
                    ]);

                    DB::afterCommit(function () use ($order): void {
                        OrderImported::dispatch($order, 'orders/create', true);
                    });
                } else {
                    $this->info('shopify.order_created.idempotent_skip', [
                        'order_id' => $order->id,
                        'shopify_order_id' => $order->shopify_order_id,
                    ]);

                    DB::afterCommit(function () use ($order): void {
                        OrderImported::dispatch($order, 'orders/create', false);
                    });
                }

                return $order;
            });
        } catch (Throwable $e) {
            $this->error('shopify.order_created.failed', [
                'shopify_order_id' => $dto->shopifyOrderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // orders/paid
    // -------------------------------------------------------------------------

    public function processOrderPaid(array $payload): Order
    {
        $dto = $this->buildPayload($payload);

        try {
            return DB::transaction(function () use ($dto): Order {
                /** @var Order $order */
                $order = Order::firstOrCreate(
                    ['shopify_order_id' => $dto->shopifyOrderId],
                    [
                        'customer_email' => $dto->customerEmail,
                        'customer_name' => $dto->customerName,
                        'amount_cents' => $dto->amountCents,
                        'currency' => $dto->currency,
                        'status' => OrderStatus::Paid,
                    ],
                );

                // Si la commande existait en pending → on update
                $isNew = $order->wasRecentlyCreated;
                $statusChanged = false;

                if (! $isNew && $order->status !== OrderStatus::Paid) {
                    $order->update(['status' => OrderStatus::Paid]);
                    $statusChanged = true;
                }

                $qrAlreadyExists = $order->qrCodes()->exists();

                $this->info('shopify.order_paid.processed', [
                    'order_id' => $order->id,
                    'shopify_order_id' => $order->shopify_order_id,
                    'is_new' => $isNew,
                    'status_changed' => $statusChanged,
                    'qr_exists' => $qrAlreadyExists,
                ]);

                DB::afterCommit(function () use ($order, $isNew, $qrAlreadyExists): void {
                    if ($isNew) {
                        OrderImported::dispatch($order, 'orders/paid', true);
                    }
                    OrderPaid::dispatch($order, $qrAlreadyExists);
                });

                return $order;
            });
        } catch (Throwable $e) {
            $this->error('shopify.order_paid.failed', [
                'shopify_order_id' => $dto->shopifyOrderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // orders/updated
    // -------------------------------------------------------------------------

    public function processOrderUpdated(array $payload): ?Order
    {
        $dto = $this->buildPayload($payload);

        $order = Order::where('shopify_order_id', $dto->shopifyOrderId)->first();

        if ($order === null) {
            $this->warning('shopify.order_updated.unknown_order', [
                'shopify_order_id' => $dto->shopifyOrderId,
            ]);

            return null;
        }

        return DB::transaction(function () use ($order, $dto): Order {
            $changes = [];

            // Update email si changé
            if ($dto->customerEmail !== $order->customer_email) {
                $changes['customer_email'] = ['from' => $order->customer_email, 'to' => $dto->customerEmail];
            }

            // Update nom si changé
            if ($dto->customerName !== $order->customer_name) {
                $changes['customer_name'] = ['from' => $order->customer_name, 'to' => $dto->customerName];
            }

            // Update montant si changé
            if ($dto->amountCents !== $order->amount_cents) {
                $changes['amount_cents'] = ['from' => $order->amount_cents, 'to' => $dto->amountCents];
            }

            // Update statut (sans jamais rétrograder un QR déjà généré)
            $newStatus = $dto->toOrderStatus();
            $statusChanged = false;

            if ($newStatus !== $order->status) {
                // Règle : on n'annule JAMAIS via orders/updated — utiliser orders/cancelled
                if ($newStatus === OrderStatus::Cancelled) {
                    $this->info('shopify.order_updated.skipped_cancellation', [
                        'order_id' => $order->id,
                    ]);
                } else {
                    $changes['status'] = ['from' => $order->status, 'to' => $newStatus];
                    $order->status = $newStatus;
                    $statusChanged = true;
                }
            }

            if (! empty($changes)) {
                $order->save();
            }

            $this->info('shopify.order_updated.processed', [
                'order_id' => $order->id,
                'shopify_order_id' => $order->shopify_order_id,
                'has_changes' => ! empty($changes),
                'status_changed' => $statusChanged,
            ]);

            DB::afterCommit(function () use ($order, $changes): void {
                OrderUpdated::dispatch($order, $changes);
            });

            // Si la mise à jour fait passer le statut à paid → on déclenche le QR
            if ($statusChanged && $newStatus === OrderStatus::Paid) {
                DB::afterCommit(function () use ($order): void {
                    OrderPaid::dispatch($order, $order->qrCodes()->exists());
                });
            }

            return $order->refresh();
        });
    }

    // -------------------------------------------------------------------------
    // orders/cancelled
    // -------------------------------------------------------------------------

    public function processOrderCancelled(array $payload): ?Order
    {
        $dto = $this->buildPayload($payload);

        $order = Order::where('shopify_order_id', $dto->shopifyOrderId)->first();

        if ($order === null) {
            $this->warning('shopify.order_cancelled.unknown_order', [
                'shopify_order_id' => $dto->shopifyOrderId,
            ]);

            return null;
        }

        return DB::transaction(function () use ($order, $dto): Order {
            $order->update([
                'status' => OrderStatus::Cancelled,
            ]);

            // Révoque le QR actif s'il existe.
            // Un QR annulé ne doit pas pouvoir être scanné même si le client
            // a déjà ouvert l'email.
            $revokedCount = 0;
            $activeQrs = $order->qrCodes()->where('status', QrStatus::Active)->get();

            foreach ($activeQrs as $qr) {
                $qr->update(['status' => QrStatus::Revoked]);
                $revokedCount++;
            }

            $this->info('shopify.order_cancelled.processed', [
                'order_id' => $order->id,
                'shopify_order_id' => $order->shopify_order_id,
                'qr_revoked' => $revokedCount,
                'cancelled_at' => $dto->cancelledAt,
            ]);

            DB::afterCommit(function () use ($order, $dto, $revokedCount): void {
                OrderCancelled::dispatch($order, $dto->cancelledAt, $revokedCount);
            });

            return $order->refresh();
        });
    }

    // -------------------------------------------------------------------------
    // orders/delete
    // -------------------------------------------------------------------------

    public function processOrderDeleted(array $payload): bool
    {
        $dto = $this->buildPayload($payload);

        $order = Order::where('shopify_order_id', $dto->shopifyOrderId)->first();

        if ($order === null) {
            $this->warning('shopify.order_deleted.unknown_order', [
                'shopify_order_id' => $dto->shopifyOrderId,
            ]);

            return false;
        }

        try {
            return DB::transaction(function () use ($order): bool {
                // Révoque tous les QR Codes avant suppression
                $order->qrCodes()->update(['status' => QrStatus::Revoked]);

                // Note : on SUPPRIME vraiment l'Order ici (obligation RGPD).
                // Les validations associées sont en cascade (FK migration).
                $order->delete();

                $this->info('shopify.order_deleted.processed', [
                    'order_id' => $order->id,
                    'shopify_order_id' => $order->shopify_order_id,
                ]);

                return true;
            });
        } catch (Throwable $e) {
            $this->error('shopify.order_deleted.failed', [
                'shopify_order_id' => $order->shopify_order_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // refunds/create
    // -------------------------------------------------------------------------

    public function processRefundCreated(array $payload): ?Order
    {
        $dto = $this->buildPayload($payload);

        $order = Order::where('shopify_order_id', $dto->shopifyOrderId)->first();

        if ($order === null) {
            $this->warning('shopify.refund_created.unknown_order', [
                'shopify_order_id' => $dto->shopifyOrderId,
            ]);

            return null;
        }

        return DB::transaction(function () use ($order, $dto): Order {
            $revokedCount = 0;

            // Si la commande est remboursée intégralement → annulation + révocation
            if ($dto->isCancelled()) {
                $order->update(['status' => OrderStatus::Cancelled]);

                $activeQrs = $order->qrCodes()->where('status', QrStatus::Active)->get();

                foreach ($activeQrs as $qr) {
                    $qr->update(['status' => QrStatus::Revoked]);
                    $revokedCount++;
                }
            }

            $this->info('shopify.refund_created.processed', [
                'order_id' => $order->id,
                'shopify_order_id' => $order->shopify_order_id,
                'fully_cancelled' => $dto->isCancelled(),
                'qr_revoked' => $revokedCount,
            ]);

            return $order->refresh();
        });
    }

    // -------------------------------------------------------------------------
    // app/uninstalled
    // -------------------------------------------------------------------------

    public function processAppUninstalled(array $payload): void
    {
        $shopDomain = $payload['domain'] ?? null;

        $this->warning('shopify.app_uninstalled.received', [
            'shop_domain' => $shopDomain,
            'payload_keys' => array_keys($payload),
        ]);

        AppUninstalled::dispatch(
            shopDomain: is_string($shopDomain) ? $shopDomain : 'unknown',
            occurredAt: now()->toIso8601String(),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers publics
    // -------------------------------------------------------------------------

    public function buildPayload(array $payload): OrderPayload
    {
        // Compatibilité ascendante avec l'ancien format InvalidPayloadException
        // (utilisée par GenerateAndSendQrJob pour le DTO legacy).
        try {
            return OrderPayload::fromShopify($payload);
        } catch (InvalidWebhookException $e) {
            Log::warning('[shopify] build_payload_failed', [
                'error' => $e->getMessage(),
                'payload_keys' => is_array($payload) ? array_keys($payload) : null,
                'has_id' => isset($payload['id']),
            ]);

            // On convertit vers l'exception legacy attendue par d'autres couches
            throw new InvalidPayloadException($e->getMessage(), previous: $e);
        }
    }
}
