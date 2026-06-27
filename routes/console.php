<?php

declare(strict_types=1);

use App\Jobs\CleanupOldValidationsJob;
use App\Jobs\ExpireQrCodesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Commandes Artisan custom
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler — tâches planifiées
|--------------------------------------------------------------------------
|
| Toutes ces tâches sont exécutées par `php artisan schedule:run`
| qui doit être appelé chaque minute par le cron système.
|
| En production (cf. SPECS §8.3) :
|   * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
|
| Stratégies appliquées :
|  - withoutOverlapping() → empêche les exécutions parallèles (race)
|  - runInBackground()      → les Jobs longs ne bloquent pas le scheduler
|  - evenInMaintenanceMode → le scheduler doit tourner même en maintenance
|  - onOneServer()          → sécurité si plusieurs machines partagent Redis
|
*/

// ---------------------------------------------------------------------------
// QR Codes expirés → bascule en status='expired'
// Toutes les heures. Indispensable : un QR expiré doit être marqué rapidement
// pour que le scan retourne 410 et non 'valid'.
// ---------------------------------------------------------------------------
Schedule::job(new ExpireQrCodesJob())
    ->hourly()
    ->withoutOverlapping(10)     // lock 10 min entre exécutions
    ->onOneServer()
    ->description('Expire les QR Codes dont expires_at est dépassé');

// ---------------------------------------------------------------------------
// Nettoyage des anciennes validations
// Chaque dimanche à 02:00. Rétention via config('qr.cleanup_days').
// ---------------------------------------------------------------------------
Schedule::job(new CleanupOldValidationsJob())
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping(60)     // lock 1h (peut durer longtemps)
    ->onOneServer()
    ->description('Purge les validations au-delà de la rétention');

// ---------------------------------------------------------------------------
// Monitoring de la Queue
// Alerte si > 100 jobs en attente. Sans cette tâche, un engorgement passerait
// inaperçu jusqu'au prochain incident utilisateur.
// ---------------------------------------------------------------------------
Schedule::command('queue:monitor --max=100')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Surveille la taille de la queue (alerte > 100)');

// ---------------------------------------------------------------------------
// (Optionnel) Vidage du cache applicatif quotidien
// Force le recalcul des KPIs et stats partenaires au pire toutes les 24h,
// en plus des TTL internes aux Services.
// ---------------------------------------------------------------------------
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::flush();
})
    ->dailyAt('03:00')
    ->name('cache-flush')
    ->withoutOverlapping(60)
    ->onOneServer()
    ->description('Vide le cache applicatif quotidien');

// ---------------------------------------------------------------------------
// Shopify — purge des événements webhook anciens
// Chaque jour à 04:00. Rétention via config('shopify.replay_protection.ttl_days').
// ---------------------------------------------------------------------------
Schedule::job(new \App\Jobs\Shopify\PruneShopifyWebhookEventsJob())
    ->dailyAt('04:00')
    ->withoutOverlapping(60)
    ->onOneServer()
    ->description('Purge les événements webhook Shopify au-delà de la rétention');

// ---------------------------------------------------------------------------
// Shopify — purge de l'historique de rotation des secrets
// Chaque semaine, pour éviter une croissance illimitée du fichier de rotation.
// ---------------------------------------------------------------------------
Schedule::command('shopify:prune-secret-rotation --keep=10')
    ->weekly()
    ->sundays()
    ->at('03:30')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->description('Purge l\'historique de rotation des secrets Shopify');