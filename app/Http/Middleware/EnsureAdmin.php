<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie que l'utilisateur authentifié via session web
 * possède le rôle 'admin'.
 *
 * S'exécute APRÈS le middleware auth (guard web).
 * Redirige vers la page de login si non authentifié,
 * retourne 403 si authentifié mais sans le bon rôle.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->role !== Role::Admin && $user->role !== Role::Partner) {
            abort(Response::HTTP_FORBIDDEN, 'Accès réservé aux administrateurs et partenaires.');
        }

        return $next($request);
    }
}
