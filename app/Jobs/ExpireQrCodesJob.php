<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\QrStatus;
use App\Models\QrCode;
use Illuminate\Support\Facades\Log;

/**
 * Job de bascule automatique des QR Codes expirés.
 *
 * Lancé hourly par le Scheduler (cf. routes/console.php).
 *
 * Pourquoi chunkById(500) :
 *  - Évite OOM si on a 100k+ QR à expirer d'un coup (ex: après incident)
 *  - Utilise l'index idx_qr_status_date sur (status, created_at)
 *  - chunkById itère par ID croissant → stable, sans saut
 *
 * Pourquoi withoutOverlapping côté Scheduler :
 *  - Empêche deux exécutions parallèles (course condition entre batches)
 *
 * Note : ce Job n'a pas besoin de retry (opération idempotente de masse).
 * En cas d'erreur, le chunk suivant sera traité à la prochaine exécution.
 */
final class ExpireQrCodesJob extends BaseJob
{
    /**
     * Taille d'un chunk. Équilibre entre mémoire et nombre de requêtes.
     * 500 = ~500 UPDATE par round, suffisant pour 1M+ de lignes en 2k rounds.
     */
    private const CHUNK_SIZE = 500;

    /**
     * Une seule tentative suffit : c'est un batch idempotent.
     * Si ça échoue au milieu, le prochain tour rattrapera les QR non expirés.
     */
    public int $tries = 1;

    /**
     * Timeout long : peut traiter beaucoup de QR en chunk.
     */
    public int $timeout = 600;

    public function handle(): void
    {
        $startedAt = microtime(true);
        $totalProcessed = 0;

        // LockForUpdate évite qu'un scan concurrent passe entre le SELECT et l'UPDATE.
        // En pratique, ValidationService utilise déjà SELECT FOR UPDATE + retry,
        // mais ici on est dans un Job scheduler : aucune race attendue.
        QrCode::query()
            ->where('status', QrStatus::Active)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->orderBy('id') // requis pour chunkById
            ->chunkById(self::CHUNK_SIZE, function ($qrCodes) use (&$totalProcessed): void {
                // On itère sur la collection et on update en masse via les IDs.
                // Plus efficace que $qrCodes->each->update() qui ferait N requêtes.
                $ids = $qrCodes->pluck('id')->all();

                $affected = QrCode::whereIn('id', $ids)
                    ->where('status', QrStatus::Active) // double check (race safety)
                    ->update(['status' => QrStatus::Expired->value]);

                $totalProcessed += $affected;
            }, 'id'); // colonne à utiliser pour le chunking

        $duration = (int) ((microtime(true) - $startedAt) * 1000);

        if ($totalProcessed > 0) {
            Log::info('[job] qr_codes_expired', [
                'count' => $totalProcessed,
                'duration_ms' => $duration,
            ]);
        } else {
            // Pas de log pour éviter de polluer les logs en cas d'absence de travail.
            Log::debug('[job] qr_codes_expired_none', [
                'duration_ms' => $duration,
            ]);
        }
    }
}
