<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\QrCodeMailable;
use App\Models\QrCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Job d'envoi de l'email contenant le QR Code au client.
 *
 * Pourquoi un Job dédié (et pas directement ShouldQueue dans le Mailable) :
 *  - Permet d'instrumenter l'envoi (logs structurés, métriques)
 *  - Permet un hook failed() plus riche que celui du Mailable
 *  - Permet de rejouer uniquement l'envoi sans régénérer le QR
 *
 * Dispatché par : GenerateAndSendQrJob::handle()
 * Déclenche : Mail::send(new QrCodeMailable($qrCode))
 *
 * Backoff plus long que le générateur : les problèmes SMTP sont souvent
 * temporaires (rate limit du provider, maintenance) — on laisse du temps.
 */
final class SendQrCodeEmailJob extends BaseJob
{
    /**
     * Backoff spécifique pour ce Job (en secondes) : 2min, 10min, 30min.
     * Cf. SPECS §8.2.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [120, 600, 1800];
    }

    public function __construct(
        public readonly int $qrCodeId,
    ) {}

    public function handle(): void
    {
        // Eager load de la relation order pour éviter N+1 dans le Mailable.
        $qrCode = QrCode::with('order')->find($this->qrCodeId);

        if ($qrCode === null) {
            Log::warning('[job] qr_code_not_found', [
                'qr_id' => $this->qrCodeId,
            ]);

            return;
        }

        // Garde-fou : si le QR a été révoqué ou la commande annulée entre-temps,
        // on annule silencieusement (pas d'envoi).
        if ($qrCode->isRevoked() || $qrCode->order?->isCancelled()) {
            Log::info('[job] skip_email_for_invalidated_qr', [
                'qr_id' => $qrCode->id,
                'qr_status' => $qrCode->status->value,
                'order_status' => $qrCode->order?->status->value,
            ]);

            return;
        }

        $recipient = $qrCode->order->customer_email;

        try {
            // Mailable implements ShouldQueue → Mail::send dispatche sur la queue.
            // On peut donc utiliser Mail::send ici sans bloquer le worker.
            Mail::to($recipient)->send(new QrCodeMailable($qrCode));

            Log::info('[job] qr_email_sent', [
                'qr_id' => $qrCode->id,
                'order_id' => $qrCode->order_id,
                'attempt' => $this->attempts(),
                // pas de log de l'email complet (RGPD)
            ]);
        } catch (Throwable $e) {
            Log::error('[job] qr_email_send_failed', [
                'qr_id' => $qrCode->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // On relance : Laravel applique le backoff. Au bout de tries échoués,
            // BaseJob::failed() sera appelé → alerte admin.
            throw $e;
        }
    }

    /**
     * Hook après échec définitif.
     *
     * Dispatche une alerte admin DÉDIÉE aux échecs email persistants,
     * avec contexte métier riche (qr_code_id, attempts, raison SMTP).
     * Cette notification est en plus de la notification générique
     * BaseJob::failed() car elle est plus actionnable côté admin.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[job] qr_email_failed_permanently', [
            'qr_id' => $this->qrCodeId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Notification dédiée (en plus de BaseJob::failed() qui dispatche le générique).
        try {
            NotifyAdminOnPersistentEmailFailureJob::dispatch(
                qrCodeId: $this->qrCodeId,
                reason: $exception->getMessage(),
                attempts: $this->attempts(),
            );
        } catch (Throwable $e) {
            Log::critical('[job] failed_to_dispatch_persistent_email_alert', [
                'qr_id' => $this->qrCodeId,
                'dispatch_error' => $e->getMessage(),
            ]);
        }

        // Chaîne avec BaseJob::failed() pour la notification générique.
        parent::failed($exception);
    }

    /**
     * Contexte additionnel pour NotifyAdminOnErrorJob.
     *
     * @return array<string, mixed>
     */
    protected function failureContext(): array
    {
        return [
            'qr_code_id' => $this->qrCodeId,
        ];
    }
}
