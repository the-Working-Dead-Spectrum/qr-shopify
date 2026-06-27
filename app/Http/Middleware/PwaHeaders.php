<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ajoute les en-têtes nécessaires pour la PWA.
 */
final class PwaHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ajouter les en-têtes PWA uniquement pour les routes PWA
        if (str_starts_with($request->path(), 'pwa')) {
            $response->headers->set('Service-Worker-Allowed', '/');
        }

        return $response;
    }
}
