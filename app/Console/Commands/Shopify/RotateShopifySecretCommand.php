<?php

declare(strict_types=1);

namespace App\Console\Commands\Shopify;

use App\Services\Shopify\ShopifySecretRotationService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Commande de rotation du secret webhook Shopify.
 *
 * Workflow typique :
 *   1. `php artisan shopify:rotate-secret --start`    → démarre la rotation
 *      (configure le nouveau secret dans le Dashboard Shopify)
 *   2. [Attendre 24h — fenêtre de grâce]
 *   3. `php artisan shopify:rotate-secret --finalize` → finalise
 *
 * Pourquoi cette stratégie : Shopify ne propose pas de swap atomique.
 * Si on supprime l'ancien secret AVANT d'avoir confirmé que le nouveau
 * est actif partout, on perd tous les webhooks pendant la transition.
 *
 * Documentation Shopify :
 *  https://shopify.dev/docs/apps/webhooks/configuration/rotate-webhook-secret
 */
final class RotateShopifySecretCommand extends Command
{
    protected $signature = 'shopify:rotate-secret
                            {--start : Démarre une rotation avec un nouveau secret}
                            {--finalize : Finalise une rotation en cours}
                            {--cancel : Annule une rotation en cours}
                            {--status : Affiche l\'état actuel}
                            {--new-secret= : Nouveau secret à utiliser (sinon génération aléatoire)}
                            {--reason=manual : Raison de la rotation (audit log)}';

    protected $description = 'Gère la rotation gracieuse du secret webhook Shopify';

    public function handle(ShopifySecretRotationService $service): int
    {
        if ($this->option('status')) {
            return $this->showStatus($service);
        }

        if ($this->option('start')) {
            return $this->startRotation($service);
        }

        if ($this->option('finalize')) {
            return $this->finalizeRotation($service);
        }

        if ($this->option('cancel')) {
            return $this->cancelRotation($service);
        }

        $this->error('Aucune action spécifiée. Utilisez --start, --finalize, --cancel ou --status.');
        $this->line('');
        $this->line('Exemples :');
        $this->line('  php artisan shopify:rotate-secret --start');
        $this->line('  php artisan shopify:rotate-secret --start --reason=quarterly_rotation');
        $this->line('  php artisan shopify:rotate-secret --finalize');
        $this->line('  php artisan shopify:rotate-secret --cancel');
        $this->line('  php artisan shopify:rotate-secret --status');

        return self::FAILURE;
    }

    private function startRotation(ShopifySecretRotationService $service): int
    {
        $newSecret = (string) ($this->option('new-secret') ?? '');

        if ($newSecret === '') {
            // Génère un secret de 64 caractères base64-url-safe
            $newSecret = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        }

        $reason = (string) $this->option('reason');

        try {
            $service->startRotation($newSecret, $reason);

            $this->info('✅ Rotation démarrée.');
            $this->line('');
            $this->warn('⚠️  PROCHAINES ÉTAPES :');
            $this->line('  1. Configurez ce nouveau secret dans Shopify :');
            $this->line("     Admin > Paramètres > Notifications > Webhooks > {$newSecret}");
            $this->line('  2. Attendez la fenêtre de grâce ('.ShopifySecretRotationService::GRACE_PERIOD_HOURS.'h).');
            $this->line('  3. Vérifiez que tous les webhooks sont bien reçus :');
            $this->line('     php artisan shopify:rotate-secret --status');
            $this->line('  4. Finalisez la rotation :');
            $this->line('     php artisan shopify:rotate-secret --finalize');
            $this->line('');
            $this->line("Nouveau secret (à copier dans Shopify) : {$newSecret}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Échec : '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function finalizeRotation(ShopifySecretRotationService $service): int
    {
        $result = $service->finalizeRotation();

        if ($result) {
            $this->info('✅ Rotation finalisée.');
            $this->warn('⚠️  Vous pouvez maintenant supprimer l\'ancien secret côté Shopify.');

            return self::SUCCESS;
        }

        $this->error('❌ Aucune rotation en cours.');

        return self::FAILURE;
    }

    private function cancelRotation(ShopifySecretRotationService $service): int
    {
        $result = $service->cancelRotation();

        if ($result) {
            $this->warn('⚠️  Rotation annulée.');

            return self::SUCCESS;
        }

        $this->error('❌ Aucune rotation en cours.');

        return self::FAILURE;
    }

    private function showStatus(ShopifySecretRotationService $service): int
    {
        $state = $service->getState();

        $this->line('État de la rotation du secret Shopify :');
        $this->line('');

        if ($state['status'] === 'no_state') {
            $this->info('Aucun état de rotation persisté. Le secret actif est celui de .env');

            return self::SUCCESS;
        }

        $this->line("  Status          : {$state['status']}");
        $this->line("  Current set at  : {$state['current_set_at']}");

        if (isset($state['pending'])) {
            $this->line("  Pending secret  : OUI (expires at {$state['pending']['expires_at']})");
            $this->line("  Pending reason  : {$state['pending']['reason']}");
        } else {
            $this->line('  Pending secret  : NON');
        }

        $this->line("  History entries : {$state['history_count']}");

        return self::SUCCESS;
    }
}
