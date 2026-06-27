<?php

declare(strict_types=1);

namespace App\Events\Shopify;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis dès qu'une commande Shopify est importée pour la première fois.
 *
 * Order::wasRecentlyCreated === true → on génère le QR.
 * Order::wasRecentlyCreated === false → idempotence (webhook déjà traité).
 *
 * Listeners typiques :
 *  - TriggerGenerateQrListener → dispatch GenerateAndSendQrJob
 *  - LogShopifyActivityListener → écrit en ActivityLog
 *  - NotifyShopifyAdminListener → alerte Slack/Discord (optionnel)
 */
final class OrderImported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $topic = 'orders/create',
        public readonly bool $isNew = true,
    ) {}
}
