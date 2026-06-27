<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DashboardServiceInterface;
use App\Contracts\ValidationServiceInterface;
use App\Enums\QrStatus;
use App\Models\Partner;
use App\Models\QrCode;
use App\Models\Validation;
use App\Services\Concerns\LogsServiceActivity;
use App\Services\Support\ValidationResult;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Service de validation d'un QR Code scanné par un partenaire.
 *
 * INVARIANT CRITIQUE : deux scans concurrents sur le même UUID ne peuvent
 * JAMAIS retourner 'valid' tous les deux. Implémenté par :
 *
 *   1. DB::transaction() — atomicité des 2 écritures (UPDATE qr_codes + INSERT validations)
 *   2. SELECT ... FOR UPDATE — verrou pessimiste exclusif sur la ligne qr_codes
 *   3. attempts: 3 — retry automatique sur deadlock MySQL (SQLSTATE 40001)
 *
 * Scénarios couverts (cf. SPECS §6.3) :
 *  - valid        : SELECT → UPDATE status='used' → INSERT validations → 200
 *  - already_used : SELECT → status='used' → 409
 *  - expired      : SELECT → expires_at < NOW() → 410
 *  - revoked      : SELECT → status='revoked' → 403
 *  - invalid      : SELECT → null (UUID inconnu) → 404
 *
 * Tous les refus (sauf invalid) sont AUSSI loggés dans `validations`
 * avec status='failed' — utile pour audit et détection d'anomalies.
 */
final class ValidationService implements ValidationServiceInterface
{
    use LogsServiceActivity;

    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
    ) {}

    /**
     * Point d'entrée principal.
     * Délègue à attempt() pour bénéficier du retry sur deadlock.
     */
    public function validate(
        string $uuid,
        Partner $partner,
        ?string $ip = null,
        ?string $userAgent = null,
    ): ValidationResult {
        $startedAt = microtime(true);

        try {
            // SPECS §6.3 : retry x3 sur deadlock.
            // On n'utilise pas DB::transaction() directement ici car on a besoin
            // d'une boucle de retry externe (Laravel ne rejoue pas la transaction
            // elle-même en cas de deadlock, seulement les jobs).
            $result = $this->attempt($uuid, $partner, $ip, $userAgent);
        } catch (Throwable $e) {
            $this->error('validation.unexpected_error', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        // Invalidation des caches dashboard — la comptabilisation change.
        $this->dashboard->invalidateAfterValidation($partner->id);

        $this->info('validation.processed', [
            'partner_id' => $partner->id,
            'status' => $result->status->value,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            // Pas de log du UUID complet (cf. SPECS §16.2)
        ]);

        return $result;
    }

    // -------------------------------------------------------------------------
    // Cœur métier — encapsulé pour permettre le retry sur deadlock
    // -------------------------------------------------------------------------

    private function attempt(
        string $uuid,
        Partner $partner,
        ?string $ip,
        ?string $userAgent,
    ): ValidationResult {
        $maxAttempts = 3;
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction(function () use ($uuid, $partner, $ip, $userAgent): ValidationResult {
                    // SELECT FOR UPDATE : verrou pessimiste exclusif sur la ligne.
                    // Le deuxième appelant attend la libération du verrou, puis
                    // lit le status mis à jour ('used') et retourne already_used.
                    /** @var QrCode|null $qrCode */
                    $qrCode = QrCode::where('uuid', $uuid)->lockForUpdate()->first();

                    // Cas 1 : UUID inconnu → 404
                    if ($qrCode === null) {
                        $this->logFailedScan(null, $partner, 'invalid', $ip, $userAgent);

                        return ValidationResult::invalid();
                    }

                    // L'ordre des vérifications est IMPORTANT :
                    // revoked > already_used > expired — chacune a priorité métier.
                    // revoked l'emporte : un admin a explicitement invalidé le QR,
                    // peu importe son état précédent.

                    // Cas 2 : QR révoqué → 403
                    if ($qrCode->isRevoked()) {
                        $this->logFailedScan($qrCode, $partner, 'revoked', $ip, $userAgent);

                        return ValidationResult::revoked($qrCode);
                    }

                    // Cas 3 : QR déjà utilisé → 409
                    if ($qrCode->isUsed()) {
                        $this->logFailedScan($qrCode, $partner, 'already_used', $ip, $userAgent);

                        return ValidationResult::alreadyUsed($qrCode);
                    }

                    // Cas 4 : QR expiré → 410
                    if ($qrCode->isExpired()) {
                        $this->logFailedScan($qrCode, $partner, 'expired', $ip, $userAgent);

                        return ValidationResult::expired($qrCode);
                    }

                    // Cas 5 : QR valide → UPDATE + INSERT
                    // On utilise le modèle en mémoire (deuxième flush inutile).
                    $qrCode->forceFill([
                        'status' => QrStatus::Used,
                        'used_at' => now(),
                        'partner_id' => $partner->id,
                    ])->save();

                    Validation::create([
                        'qr_code_id' => $qrCode->id,
                        'partner_id' => $partner->id,
                        'scanned_at' => now(),
                        'status' => 'valid',
                        'ip_address' => $ip,
                        'user_agent' => $userAgent,
                        'created_at' => now(),
                    ]);

                    return ValidationResult::valid($qrCode, $partner->id);
                });
            } catch (QueryException $e) {
                // SQLSTATE 40001 = serialization failure / deadlock detected.
                // On retry jusqu'à 3 fois, puis on propage.
                if ($this->isDeadlock($e) && $attempt < $maxAttempts) {
                    $this->warning('validation.deadlock_retry', [
                        'attempt' => $attempt,
                        'partner_id' => $partner->id,
                    ]);

                    // Backoff exponentiel léger : 10ms, 30ms
                    usleep(10_000 * $attempt);

                    continue;
                }

                throw $e;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Logue un scan refusé dans `validations` pour audit.
     * Crée l'entrée même quand le scan échoue : permet de détecter des abus.
     */
    private function logFailedScan(
        ?QrCode $qrCode,
        Partner $partner,
        string $reason,
        ?string $ip,
        ?string $userAgent,
    ): void {
        Validation::create([
            'qr_code_id' => $qrCode?->id,
            'partner_id' => $partner->id,
            'scanned_at' => now(),
            'status' => 'failed',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);

        $this->warning('validation.refused', [
            'partner_id' => $partner->id,
            'reason' => $reason,
            // log du qr_id interne (pas l'UUID public) pour audit croisé.
            'qr_id' => $qrCode?->id,
        ]);
    }

    /**
     * Détecte un deadlock MySQL (SQLSTATE 40001).
     * MySQL 8 utilise code 1213 ; on accepte aussi les autres variantes.
     */
    private function isDeadlock(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $code = $e->errorInfo[1] ?? null;

        // SQLSTATE 40001 = transaction rollback due to deadlock
        // MySQL error 1213 = deadlock found when trying to get lock
        // MySQL error 1205 = lock wait timeout exceeded
        return $sqlState === '40001'
            || in_array($code, [1213, 1205], true);
    }
}
