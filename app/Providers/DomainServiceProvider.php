<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\DashboardServiceInterface;
use App\Contracts\QrCodeGeneratorInterface;
use App\Contracts\ShopifyServiceInterface;
use App\Contracts\ValidationServiceInterface;
use App\Services\DashboardService;
use App\Services\QrCodeService;
use App\Services\ShopifyService;
use App\Services\ValidationService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

/**
 * Provider dédié au domaine métier (services applicatifs).
 *
 * Responsabilités :
 *  - Bind interface → implémentation (IoC) pour permettre la substitution
 *    (ex: mock en test, autre implémentation en prod)
 *  - Singleton pour les services stateless (réduit la pression sur le container)
 *  - Pas de logique métier ici — uniquement de l'enregistrement DI
 *
 * Chargé depuis bootstrap/providers.php (Laravel 11+).
 */
final class DomainServiceProvider extends ServiceProvider
{
    /**
     * Services stateless enregistrés en singleton.
     * Pas besoin d'instance par requête : aucune propriété mutable.
     *
     * @var array<class-string, class-string>
     */
    private const SINGLETONS = [
        QrCodeGeneratorInterface::class => QrCodeService::class,
        ShopifyServiceInterface::class => ShopifyService::class,
        ValidationServiceInterface::class => ValidationService::class,
        DashboardServiceInterface::class => DashboardService::class,
    ];

    public function register(): void
    {
        // Bindings interface → implémentation.
        // On utilise bind() (et non singleton()) pour que le container
        // gère correctement les dépendances injectées dans le constructeur.
        // Les services sont stateless, donc même recréés à chaque resolve,
        // ils restent économiques.
        foreach (self::SINGLETONS as $contract => $concrete) {
            $this->app->bind($contract, $concrete);
        }

        // Injection du repository cache dans les services qui en ont besoin.
        // On centralise ici pour permettre aux tests de surcharger le store.
        $this->app->bind(CacheRepository::class, static fn () => Cache::store());
    }

    public function boot(): void
    {
        // Pas de hooks au boot pour l'instant.
        // Les services sont résolus à la demande via le container.
    }
}
