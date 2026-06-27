<?php

declare(strict_types=1);

namespace App\Console\Commands\Shopify;

use App\Jobs\Shopify\TestShopifyConnectionJob;
use App\Services\Shopify\ShopifyClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Teste la connexion Shopify en dispatchant un job synchrone.
 *
 * Usage :
 *   php artisan shopify:test-connection
 *
 * Utile pour :
 *  - valider la configuration après déploiement
 *  - diagnostiquer les erreurs d'auth Shopify
 *  - audit périodique
 */
final class TestShopifyConnectionCommand extends Command
{
    protected $signature = 'shopify:test-connection
                            {--sync : Exécute le job de manière synchrone (au lieu d\'async)}';

    protected $description = 'Teste la connexion à l\'Admin API Shopify';

    public function handle(): int
    {
        $this->info('Dispatch du job de test...');

        if ($this->option('sync')) {
            $this->info('Mode synchrone : exécution immédiate.');

            try {
                app(TestShopifyConnectionJob::class, ['logPayload' => false])
                    ->handle(app(ShopifyClient::class));
                $this->info('✅ Connexion réussie !');

                return self::SUCCESS;
            } catch (Throwable $e) {
                $this->error('❌ Échec : '.$e->getMessage());

                return self::FAILURE;
            }
        }

        TestShopifyConnectionJob::dispatch(logPayload: false);
        $this->info('Job dispatché en async. Vérifiez storage/logs/shopify.log.');

        return self::SUCCESS;
    }
}
