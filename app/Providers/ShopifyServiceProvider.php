<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Shopify\ShopifyAdminOrderApiInterface;
use App\Contracts\Shopify\ShopifyCustomerApiInterface;
use App\Contracts\Shopify\ShopifyFulfillmentApiInterface;
use App\Contracts\Shopify\ShopifyInventoryApiInterface;
use App\Contracts\Shopify\ShopifyProductApiInterface;
use App\Contracts\ShopifyClientInterface;
use App\Contracts\ShopifyServiceInterface;
use App\Events\Shopify\AppUninstalled;
use App\Events\Shopify\OrderImported;
use App\Events\Shopify\QrSent;
use App\Listeners\Shopify\LogShopifyActivityListener;
use App\Listeners\Shopify\NotifyAdminOnUninstallListener;
use App\Listeners\Shopify\TrackQrSentListener;
use App\Listeners\Shopify\TriggerGenerateQrListener;
use App\Services\Shopify\Api\ShopifyAdminOrderService;
use App\Services\Shopify\Api\ShopifyCustomerService;
use App\Services\Shopify\Api\ShopifyFulfillmentService;
use App\Services\Shopify\Api\ShopifyInventoryService;
use App\Services\Shopify\Api\ShopifyProductService;
use App\Services\Shopify\ShopifyClient;
use App\Services\ShopifyService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider dédié à l'intégration Shopify.
 *
 * Responsabilités :
 *  - Bind les interfaces → implémentations (DI)
 *  - Override éventuel du client HTTP (utile pour les tests avec Http::fake())
 *  - Bootstrap des listeners (relation Event → Listener)
 *
 * Cohérent avec l'organisation du projet (DomainServiceProvider existe déjà).
 */
final class ShopifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Le service métier garde son implémentation existante (pas de breaking change).
        $this->app->bind(ShopifyServiceInterface::class, ShopifyService::class);

        // Le client HTTP est un singleton : on garde le timeout/headers cohérents.
        $this->app->singleton(ShopifyClientInterface::class, ShopifyClient::class);

        // Services API sortants (Resources) — utilisent le ShopifyClient injectable.
        $this->app->bind(ShopifyProductApiInterface::class, ShopifyProductService::class);
        $this->app->bind(ShopifyCustomerApiInterface::class, ShopifyCustomerService::class);
        $this->app->bind(ShopifyAdminOrderApiInterface::class, ShopifyAdminOrderService::class);
        $this->app->bind(ShopifyInventoryApiInterface::class, ShopifyInventoryService::class);
        $this->app->bind(ShopifyFulfillmentApiInterface::class, ShopifyFulfillmentService::class);
    }

    public function boot(): void
    {
        $this->registerEventListeners();
    }

    /**
     * Mapping Event → Listener pour le module Shopify.
     *
     * Utilise les tableaux typés de Laravel 11+ (Event::listen).
     * Si un listener doit être mis en queue, il faut ajouter ShouldQueue.
     */
    private function registerEventListeners(): void
    {
        $events = $this->app['events'];

        // OrderImported → génération QR + log activité
        $events->listen(
            OrderImported::class,
            TriggerGenerateQrListener::class,
        );

        $events->listen(
            OrderImported::class,
            LogShopifyActivityListener::class,
        );

        // QrSent → tracking conversion
        $events->listen(
            QrSent::class,
            TrackQrSentListener::class,
        );

        // AppUninstalled → alerte admin
        $events->listen(
            AppUninstalled::class,
            NotifyAdminOnUninstallListener::class,
        );
    }
}
