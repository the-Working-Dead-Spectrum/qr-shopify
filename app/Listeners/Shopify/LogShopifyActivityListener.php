<?php

declare(strict_types=1);

namespace App\Listeners\Shopify;

use App\Events\Shopify\OrderImported;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Listener qui enregistre l'import d'une commande Shopify en ActivityLog.
 *
 * Permet :
 *  - Audit trail complet (qui/quand/pourquoi)
 *  - Conformité RGPD (preuve de traitement)
 *  - Debug en cas de régression
 *
 * Non-bloquant : try/catch autour de l'écriture. Une erreur d'audit
 * ne doit JAMAIS bloquer le flux webhook principal.
 */
final class LogShopifyActivityListener
{
    public function handle(OrderImported $event): void
    {
        try {
            ActivityLog::record(
                action: 'shopify.order.imported',
                subjectType: OrderImported::class,
                subjectId: $event->order->id,
                meta: [
                    'topic' => $event->topic,
                    'is_new' => $event->isNew,
                    'shopify_order_id' => $event->order->shopify_order_id,
                    'amount_cents' => $event->order->amount_cents,
                    'currency' => $event->order->currency,
                ],
            );
        } catch (Throwable $e) {
            // Ne jamais faire échouer le webhook à cause d'un log.
            Log::warning('[shopify.activity_log] write_failed', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
