<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsurePartner;
use App\Http\Middleware\VerifyShopifyHmac;
use App\Http\Middleware\VerifyShopifyIp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // -----------------------------------------------------------------------
        // Aliases — noms courts utilisables dans les routes et groupes
        // -----------------------------------------------------------------------
        $middleware->alias([
            'shopify.hmac'   => VerifyShopifyHmac::class,
            'shopify.ip'     => VerifyShopifyIp::class,
            'ensure.partner' => EnsurePartner::class,
            'ensure.admin'   => EnsureAdmin::class,
            'pwa.headers'    => \App\Http\Middleware\PwaHeaders::class,
        ]);

        // -----------------------------------------------------------------------
        // Exclusion CSRF pour les webhooks Shopify
        // Shopify ne peut pas envoyer de token CSRF — on exclut les routes webhook
        // La sécurité est assurée par la vérification HMAC à la place
        // -----------------------------------------------------------------------
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        // -----------------------------------------------------------------------
        // Middleware API global
        // Sanctum statue les requêtes API via cookie ou token Bearer
        // -----------------------------------------------------------------------
        $middleware->statefulApi();

        // -----------------------------------------------------------------------
        // Rate limiting nommés (définis dans AppServiceProvider)
        // -----------------------------------------------------------------------
        // $middleware->throttleWithRedis();
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Toutes les routes /api/* retournent du JSON même en cas d'erreur non gérée
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request): bool => $request->is('api/*') || $request->is('webhooks/*')
        );

        // En production, ne pas exposer les détails des exceptions
        $exceptions->dontReportDuplicates();
    })
    ->create();
