<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Validation;
use Illuminate\Support\Facades\Log;

/**
 * Job de nettoyage des anciennes validations.
 *
 * Lancé chaque dimanche à 02:00 par le Scheduler.
 *
 * Rétention configurable via QR_CLEANUP_DAYS (défaut 180 jours, cf. SPECS §8.2).
 *
 * Pourquoi delete() par chunks plutôt que truncate :
 *  - truncate verrouillerait la table pendant le nettoyage
 *  - chunks permettent de continuer à servir les API pendant le nettoyage
 *  - En cas d'interruption (timeout), on a déjà libéré de l'espace
 *
 * Pourquoi on garde les validations liées aux QR encore présents :
 *  - Historique de scan → utile pour audit même si QR est expiré depuis longtemps
 *  - Mais la rétention à 180j reste raisonnable pour la conformité RGPD
 */
final class CleanupOldValidationsJob extends BaseJob
{
    private const CHUNK_SIZE = 1000;

    public int $tries = 1;

    /**
     * Timeout long car opération potentiellement lourde (millions de lignes).
     */
    public int $timeout = 900;

    public function handle(): void
    {
        $retentionDays = (int) config('qr.cleanup_days', 180);
        $cutoff = now()->subDays($retentionDays);

        $startedAt = microtime(true);
        $totalDeleted = 0;

        // deleteById est plus efficace que delete par chunk car on évite
        // de charger les modèles en mémoire — on fait un DELETE WHERE id IN (...)
        Validation::query()
            ->where('created_at', '<', $cutoff)
            ->select('id')
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($rows) use (&$totalDeleted): void {
                $ids = $rows->pluck('id')->all();

                $deleted = Validation::whereIn('id', $ids)->delete();
                $totalDeleted += $deleted;
            }, 'id');

        $duration = (int) ((microtime(true) - $startedAt) * 1000);

        Log::info('[job] validations_cleanup', [
            'deleted' => $totalDeleted,
            'retention_days' => $retentionDays,
            'cutoff' => $cutoff->toDateString(),
            'duration_ms' => $duration,
        ]);
    }
}
