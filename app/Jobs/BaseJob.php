<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\JobInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Classe abstraite factorisant le comportement transverse des Jobs du domaine.
 *
 * Centralise :
 *  - ShouldQueue (dispatch async via Queue worker)
 *  - backoff() par défaut : 60s, 180s, 600s (1min, 3min, 10min)
 *  - tries() par défaut : 3
 *  - failed() commun : dispatch NotifyAdminOnErrorJob + log structuré
 *
 * Les Jobs concrets n'ont plus qu'à implémenter handle() et surcharger
 * les constantes si besoin (backoff spécifique pour SendQrCodeEmailJob par ex).
 *
 * Note : InteractsWithQueue permet d'accéder à $this->job, $this->release(), $this->delete()
 * pour les patterns avancés (release avec délai, retry manuel).
 */
abstract class BaseJob implements JobInterface, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives par défaut avant échec définitif.
     * Les Jobs concrets peuvent surcharger.
     */
    public int $tries = 3;

    /**
     * Timeout global du Job (en secondes).
     * Au-delà, le worker le marque en échec.
     */
    public int $timeout = 120;

    /**
     * Backoff exponentiel par défaut entre les retries.
     * Retourné par backoff() — Laravel l'utilise tel quel.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 180, 600];
    }

    /**
     * Hook après échec définitif.
     * Appelé par Laravel une fois tries épuisées.
     *
     * Dispatch automatique d'une alerte admin. Si le dispatch lui-même échoue,
     * on log en ERROR (fallback ultime, à superviser via Sentry).
     */
    public function failed(Throwable $exception): void
    {
        // Log structuré en premier — même si la notif échoue, on a la trace.
        Log::error('[job] failed_permanently', [
            'job_class' => static::class,
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]);

        // Dispatch de l'alerte admin via le Bus (mockable en tests).
        $jobClass = 'App\\Jobs\\NotifyAdminOnErrorJob';

        if (class_exists($jobClass)) {
            try {
                $jobClass::dispatch(
                    jobClass: static::class,
                    errorMessage: $exception->getMessage(),
                    context: $this->failureContext(),
                );
            } catch (Throwable $e) {
                Log::critical('[job] failed_to_dispatch_admin_alert', [
                    'original_job' => static::class,
                    'dispatch_error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Contexte additionnel passé à NotifyAdminOnErrorJob.
     * À surcharger par les Jobs concrets pour inclure leurs données métier.
     *
     * @return array<string, mixed>
     */
    protected function failureContext(): array
    {
        return [];
    }
}
