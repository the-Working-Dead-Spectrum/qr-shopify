<?php

declare(strict_types=1);

namespace App\Listeners\Shopify;

use App\Events\Shopify\QrSent;
use App\Services\Concerns\LogsServiceActivity;

/**
 * Listener de tracking de la conversion "QR → email envoyé".
 *
 * Permet de mesurer le taux d'envoi effectif des QR codes et de détecter
 * les éventuels problèmes SMTP avant que les clients ne s'en plaignent.
 */
final class TrackQrSentListener
{
    use LogsServiceActivity;

    public function handle(QrSent $event): void
    {
        $this->info('shopify.qr.email_sent', [
            'qr_id' => $event->qrCode->id,
            'order_id' => $event->qrCode->order_id,
            // On masque l'email recipient (RGPD)
            'recipient_hash' => substr(hash('sha256', $event->recipientEmail), 0, 12),
        ]);
    }
}
