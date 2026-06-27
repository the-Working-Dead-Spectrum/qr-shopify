<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\ShopifyDashboardServiceInterface::class,
            \App\Services\ShopifyDashboardService::class
        );
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        Schema::defaultStringLength(191);
    }

    /**
     * Définit les rate limiters nommés utilisés dans bootstrap/app.php.
     *
     * 'webhook' : webhooks Shopify — élevé car Shopify peut envoyer des bursts
     *   60 requêtes/minute par IP
     *   Réponse 429 avec Retry-After si dépassé
     *
     * 'api' : endpoints partenaires — protège contre le bruteforce d'UUIDs
     *   30 requêtes/minute par user_id authentifié, ou par IP sinon
     *   Distinguer par user permet un partenaire légitime avec beaucoup de scans
     */
    private function configureRateLimiting(): void
    {
        // Rate limiter pour les webhooks Shopify
        RateLimiter::for('webhook', function (Request $request): Limit {
            return Limit::perMinute(60)
                ->by($request->ip())
                ->response(function () {
                    return response()->json(
                        ['error' => 'Trop de requêtes. Réessayez dans un moment.'],
                        429,
                    );
                });
        });

        // Rate limiter pour l'API partenaires
        // Clé composite : user_id si authentifié, IP sinon
        RateLimiter::for('api', function (Request $request): Limit {
            $key = $request->user()?->id
                ? 'user:'.$request->user()->id
                : 'ip:'.$request->ip();

            return Limit::perMinute(30)
                ->by($key)
                ->response(function () {
                    return response()->json(
                        ['error' => 'Limite de requêtes atteinte. Réessayez dans un moment.'],
                        429,
                    );
                });
        });
    }
}
