<?php

declare(strict_types=1);

namespace App\Mail;

use App\Contracts\QrCodeGeneratorInterface;
use App\Models\QrCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Email envoyé au client contenant son QR Code de validation.
 *
 * Implémente ShouldQueue → l'envoi est asynchrone (poussé via Queue).
 * Le rendu de l'image est mis en cache dans la propriété pour ne pas
 * régénérer le PNG à chaque retry du worker.
 *
 * Données exposées à la vue (cf. SPECS §9.1) :
 *  - customerName  : prénom / nom du client
 *  - qrImageBase64 : PNG encodé (inline pour contourner le blocage des images)
 *  - fallbackUrl   : lien HTTP direct si l'image ne s'affiche pas
 *  - expiresAt     : date d'expiration formatée lisible
 *
 * Pourquoi pas constructor injection du QrCodeGeneratorInterface :
 * les Mailables sont sérialisés en Queue → seules les propriétés scalaires
 * survivent à la sérialisation. On résout via app() dans build().
 */
final class QrCodeMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Image PNG base64 mise en cache au premier build() pour éviter
     * de régénérer à chaque retry du worker.
     */
    private ?string $cachedQrImage = null;

    public function __construct(
        public readonly QrCode $qrCode,
    ) {}

    public function envelope(): Envelope
    {
        $order = $this->qrCode->order;

        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name', config('app.name')),
            ),
            to: [new Address($order->customer_email, $order->customer_name ?? '')],
            subject: "Votre QR Code — Commande #{$order->shopify_order_id}",
            tags: ['qr-code', "order:{$order->id}"],
            metadata: [
                'qr_code_id' => $this->qrCode->id,
                'order_id' => $order->id,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.qr-code',
            with: [
                'customerName' => $this->qrCode->order->customer_name ?? 'Client',
                'qrImageBase64' => $this->getQrImage(),
                'fallbackUrl' => $this->qrCode->getPublicUrl(),
                'expiresAt' => $this->qrCode->expires_at?->format('d/m/Y'),
                'orderReference' => $this->qrCode->order->shopify_order_id,
                'appName' => config('app.name'),
            ],
        );
    }

    /**
     * Headers custom — utile pour le suivi et le list-unsubscribe (RGPD).
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Qr-Code-Id' => (string) $this->qrCode->id,
                'X-Order-Id' => (string) $this->qrCode->order_id,
            ],
        );
    }

    /**
     * Récupère (ou régénère) le PNG en base64.
     * Mise en cache en mémoire pour les retries du worker.
     */
    private function getQrImage(): string
    {
        if ($this->cachedQrImage !== null) {
            return $this->cachedQrImage;
        }

        /** @var QrCodeGeneratorInterface $generator */
        $generator = app(QrCodeGeneratorInterface::class);

        return $this->cachedQrImage = $generator->generateImage($this->qrCode->uuid);
    }
}
