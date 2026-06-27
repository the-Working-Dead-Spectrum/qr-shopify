<?php

declare(strict_types=1);

namespace App\Events\Shopify;

use App\Models\QrCode;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand l'email contenant le QR Code a été envoyé au client.
 *
 * Permet de tracker la conversion "achat → email reçu" pour le dashboard.
 */
final class QrSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly QrCode $qrCode,
        public readonly string $recipientEmail,
    ) {}
}
