<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PersistentEmailFailureAlert;
use App\Models\QrCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Job dédié à la notification admin lors d'un échec persistant d'envoi
 * d'email (QR Code non reçu après toutes les tentatives).
 *
 * Pourquoi un Job dédié et pas une simple notification dans SendQrCodeEmailJob :
 *  - SendQrCodeEmailJob peut être mis en retry sans toucher au notif admin
 *  - On veut pouvoir être notifié même si le système de queue est perturbé
 *  - Les notifs admin vont sur un canal dédié (séparation des flux)
 *
 * Quand ce Job est-il dispatché :
 *  - Dans SendQrCodeEmailJob::failed() (après 3 tentatives échouées)
 *  - Directement si l'email de la commande est invalide (MX lookup, etc.)
 *
 * ⚠️ N'hérite PAS de BaseJob : pas de retry (notification critique mais
 * non bloquante), pas de failed() chaîné.
 */
final class NotifyAdminOnPersistentEmailFailureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(
        public readonly int $qrCodeId,
        public readonly string $reason,
        public readonly int $attempts,
    ) {}

    public function handle(): void
    {
        $qrCode = QrCode::with('order')->find($this->qrCodeId);

        if ($qrCode === null) {
            Log::warning('[notify] qr_code_not_found_for_failure', [
                'qr_code_id' => $this->qrCodeId,
            ]);

            return;
        }

        $adminEmail = config('mail.admin_email')
            ?: env('ADMIN_EMAIL')
            ?: null;

        if (empty($adminEmail)) {
            Log::critical('[notify] no_admin_email_configured_for_email_failure', [
                'qr_code_id' => $this->qrCodeId,
                'reason' => $this->reason,
            ]);

            return;
        }

        try {
            Mail::to($adminEmail)->send(
                new PersistentEmailFailureAlert(
                    qrCodeId: $this->qrCodeId,
                    orderId: (int) ($qrCode->order_id ?? 0),
                    recipient: (string) ($qrCode->order?->customer_email ?? ''),
                    reason: $this->reason,
                    attempts: $this->attempts,
                ),
            );

            Log::error('[notify] persistent_email_failure_admin_alerted', [
                'qr_code_id' => $this->qrCodeId,
                'attempts' => $this->attempts,
            ]);
        } catch (Throwable $e) {
            // SMTP admin down → on log en CRITICAL pour Sentry/Datadog.
            Log::critical('[notify] admin_alert_email_send_failed', [
                'qr_code_id' => $this->qrCodeId,
                'original_error' => $this->reason,
                'smtp_error' => $e->getMessage(),
            ]);
        }
    }
}
