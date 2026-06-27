<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\QrCodeGeneratorInterface;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job de génération du QR Code + chaînage de l'envoi email.
 *
 * Flux :
 *  1. Recharge l'Order (peut avoir été annulée entre temps → idempotent)
 *  2. Si commande annulée : on annule le Job silencieusement (no-op)
 *  3. Génère le QR via QrCodeService
 *  4. Dispatch SendQrCodeEmailJob pour l'envoi
 *
 * Découplé de ShopifyService via DI container : le Service ne fait que
 * dispatcher, le Job gère ses propres dépendances via le constructeur.
 *
 * Dispatché par : ShopifyService::processOrderPaid()
 * Enqueue sur : connexion par défaut, queue 'default'
 *
 * Note : l'Order n'est pas sérialisé via SerializesModels car on n'en a
 * besoin qu'au premier essai. Pour les retries, on recharge par ID —
 * ainsi on a toujours l'état frais de la DB (annulation intervenue entre-temps).
 */
final class GenerateAndSendQrJob extends BaseJob
{
    /**
     * On garde l'ID plutôt que le modèle complet.
     * Avantage : SerializesModels rechargera depuis la DB si la sérialisation
     * est nécessaire, mais on peut aussi rebuild manuellement.
     */
    public function __construct(
        public readonly int $orderId,
    ) {}

    public function handle(): void
    {
        $order = Order::with('qrCodes')->find($this->orderId);

        if ($order === null) {
            // Order supprimée entre-temps : on ne peut rien faire, on log et on arrête.
            Log::warning('[job] order_not_found', [
                'order_id' => $this->orderId,
                'job' => self::class,
            ]);

            return;
        }

        // Garde-fou : commande annulée entre-temps, on ne génère pas le QR.
        if ($order->isCancelled()) {
            Log::info('[job] skip_cancelled_order', [
                'order_id' => $order->id,
            ]);

            return;
        }

        // Garde-fou : QR déjà généré (idempotence au cas où le Job est rejoué
        // manuellement via tinker ou par un retry après succès partiel).
        if ($order->qrCodes()->exists()) {
            Log::info('[job] qr_already_exists', [
                'order_id' => $order->id,
            ]);

            return;
        }

        /** @var QrCodeGeneratorInterface $qrService */
        $qrService = app(QrCodeGeneratorInterface::class);

        try {
            $qrCode = $qrService->generate($order);

            // Chaînage : on dispatch l'envoi email dans la foulée.
            // Bus::dispatch pour respecter les conventions Laravel 10+
            // et permettre un mock via Bus::fake() en tests.
            SendQrCodeEmailJob::dispatch($qrCode->id);
        } catch (Throwable $e) {
            // On log + on relance : Laravel appliquera le backoff et
            // marquera failed() si tries épuisées.
            Log::error('[job] qr_generation_failed', [
                'order_id' => $order->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Contexte additionnel pour NotifyAdminOnErrorJob.
     *
     * @return array<string, mixed>
     */
    protected function failureContext(): array
    {
        return [
            'order_id' => $this->orderId,
        ];
    }
}
