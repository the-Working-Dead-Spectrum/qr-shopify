<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email d'alerte admin — échec persistant d'envoi QR Code.
 *
 * Déclenché par NotifyAdminOnPersistentEmailFailureJob après que toutes
 * les tentatives de SendQrCodeEmailJob ont échoué.
 *
 * Format : email texte brut (lisibilité maximale, aucun problème de rendu).
 */
final class PersistentEmailFailureAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly int $qrCodeId,
        public readonly int $orderId,
        public readonly string $recipient,
        public readonly string $reason,
        public readonly int $attempts,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '[%s] ⚠️ Échec persistant envoi QR #%d',
                config('app.name'),
                $this->qrCodeId,
            ),
        );
    }

    public function content(): Content
    {
        $recipientRedacted = $this->recipient !== '' ? mb_substr($this->recipient, 0, 3).'***' : '(inconnu)';

        $body = <<<TEXT
        Alerte automatique — Échec persistant d'envoi d'email QR Code

        QR Code ID     : {$this->qrCodeId}
        Commande ID    : {$this->orderId}
        Destinataire   : {$recipientRedacted}
        Tentatives     : {$this->attempts}
        Date           : {$this->now()}
        Environnement  : {$this->env()}

        Raison de l'échec :
        {$this->reason}

        Actions recommandées :
        1. Vérifier la configuration SMTP (config/mail.php + .env)
        2. Vérifier que le domaine émetteur n'est pas blacklisté
        3. Contacter le client manuellement si l'adresse email est valide
        4. Générer un nouveau QR si nécessaire :
           POST /admin/orders/{$this->orderId}/qr/regenerate

        Logs détaillés :
        storage/logs/laravel-{$this->today()}.log

        ---
        Cette alerte est générée automatiquement après épuisement des tentatives
        du système d'envoi automatique.
        TEXT;

        return new Content(
            htmlString: nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')),
            text: $body,
        );
    }

    private function now(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    private function today(): string
    {
        return now()->format('Y-m-d');
    }

    private function env(): string
    {
        return (string) config('app.env');
    }
}
