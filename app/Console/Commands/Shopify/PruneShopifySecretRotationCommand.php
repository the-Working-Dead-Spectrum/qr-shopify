<?php

declare(strict_types=1);

namespace App\Console\Commands\Shopify;

use App\Services\Shopify\ShopifySecretRotationService;
use Illuminate\Console\Command;

/**
 * Commande planifiée de purge de l'historique de rotation des secrets.
 *
 * Usage :
 *  - Manuelle :  php artisan shopify:prune-secret-rotation --keep=10
 *  - Planifiée : chaque semaine dans routes/console.php
 *
 * Objectif : éviter une croissance illimitée du fichier de rotation.
 */
final class PruneShopifySecretRotationCommand extends Command
{
    protected $signature = 'shopify:prune-secret-rotation
                            {--keep=10 : Nombre d\'entrées d\'historique à conserver}';

    protected $description = 'Purge l\'historique de rotation des secrets Shopify';

    public function handle(ShopifySecretRotationService $service): int
    {
        $keep = (int) $this->option('keep');

        if ($keep < 1) {
            $this->error('--keep doit être ≥ 1');

            return self::FAILURE;
        }

        $removed = $service->pruneHistory($keep);

        if ($removed === 0) {
            $this->info('Aucune entrée à supprimer.');
        } else {
            $this->info("✅ {$removed} entrée(s) supprimée(s). {$keep} conservée(s).");
        }

        return self::SUCCESS;
    }
}
