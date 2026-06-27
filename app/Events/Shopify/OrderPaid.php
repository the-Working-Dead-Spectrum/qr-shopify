<?php

declare(strict_types=1);

namespace App\Events\Shopify;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand une commande passe au statut "paid".
 *
 * Déclenche en aval :
 *  - génération du QR Code (si pas déjà fait)
 *  - email au client
 *  - notification admin (optionnel)
 *
 * Toujours émis APRÈS validation HMAC + déduplication replay.
 */
final class OrderPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly bool $qrAlreadyGenerated = false,
    ) {}
}
