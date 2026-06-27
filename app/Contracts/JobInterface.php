<?php

declare(strict_types=1);

namespace App\Contracts;

use Throwable;

/**
 * Contrat d'un Job asynchrone exécuté par Laravel Queue.
 *
 * Pourquoi une interface : permettre à un test (ou un autre système) de
 * substituer un faux Job, et centraliser la doc métier transverse.
 *
 * Implémentation concrète : Illuminate\Contracts\Queue\ShouldQueue + BaseJob.
 */
interface JobInterface
{
    /**
     * Logique métier exécutée par le worker.
     * Toute exception doit être catchée et dispatchée via NotifyAdminOnErrorJob.
     */
    public function handle(): void;

    /**
     * Hook appelé après échec définitif (tous retries épuisés).
     * Doit dispatcher NotifyAdminOnErrorJob pour alerter l'admin.
     */
    public function failed(Throwable $exception): void;
}
