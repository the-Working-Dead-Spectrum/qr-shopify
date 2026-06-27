<?php

declare(strict_types=1);

namespace App\Events\Shopify;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand une commande est annulée côté Shopify.
 *
 * Conséquences :
 *  - Révoque le QR actif (déjà implémenté dans ShopifyService)
 *  - Trace l'annulation en ActivityLog (RGPD)
 *  - Stoppe tout email programmé mais pas encore envoyé (cancel job in queue)
 */
final class OrderCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly ?string $cancelledAt = null,
        public readonly int $qrCodesRevoked = 0,
    ) {}
}
