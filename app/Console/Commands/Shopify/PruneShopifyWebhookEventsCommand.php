<?php

declare(strict_types=1);

namespace App\Console\Commands\Shopify;

use App\Jobs\Shopify\PruneShopifyWebhookEventsJob;
use Illuminate\Console\Command;
use Throwable;

/**
 * Purge manuelle des événements webhook Shopify.
 *
 * Usage :
 *   php artisan shopify:prune-webhook-events
 *   php artisan shopify:prune-webhook-events --days=7
 *
 * Utile pour :
 *  - libérer de l'espace disque rapidement
 *  - appliquer une rétention différente temporairement
 *  - tester en staging
 */
final class PruneShopifyWebhookEventsCommand extends Command
{
    protected $signature = 'shopify:prune-webhook-events
                            {--days= : Nombre de jours de rétention (override config)}';

    protected $description = 'Purge les événements webhook Shopify au-delà de la rétention';

    public function handle(): int
    {
        $days = $this->option('days');

        if ($days !== null) {
            config(['shopify.replay_protection.ttl_days' => (int) $days]);
            $this->info("Rétention override : {$days} jours");
        }

        $this->info('Exécution synchrone du job de purge...');

        try {
            $deleted = app(PruneShopifyWebhookEventsJob::class)
                ->handle();

            $this->info("✅ {$deleted} événement(s) supprimé(s).");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Échec : '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
