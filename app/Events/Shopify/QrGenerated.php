<?php

declare(strict_types=1);

namespace App\Events\Shopify;

use App\Models\QrCode;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis dès qu'un QR Code a été généré avec succès.
 *
 * Permet de déclencher des actions transverses :
 *  - Envoi email (SendQrCodeEmailJob — historiquement appelé directement)
 *  - Analytics (conversion tracking)
 *  - Webhook sortant vers le SI du client
 */
final class QrGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly QrCode $qrCode,
    ) {}
}
