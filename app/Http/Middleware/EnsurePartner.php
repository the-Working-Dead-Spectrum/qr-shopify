<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\PartnerStatus;
use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie que l'utilisateur authentifié :
 *  1. A bien le rôle 'partner'
 *  2. Possède un enregistrement Partner associé
 *  3. Ce Partner est en statut 'active'
 *
 * Contexte double :
 *  - Pages web PWA (Blade) → redirection vers /pwa/login
 *  - Endpoints API (Sanctum) → réponse JSON 403
 *
 * Le contexte est détecté via $request->expectsJson() — cohérent avec
 * le pattern Laravel : un fetch AJAX pose Accept: application/json,
 * une navigation navigateur classique ne le fait pas.
 *
 * Partage le Partner résolu via $request->attributes pour éviter
 * une double requête SQL dans le Controller.
 */
final class EnsurePartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->unauthenticated($request);
        }

        // Vérification du rôle — un admin ne peut pas scanner
        if ($user->role !== Role::Partner) {
            return $this->forbid($request, 'Accès réservé aux partenaires.');
        }

        // Chargement du partenaire — relation eager pour éviter N+1
        $partner = $user->partner;

        // Partenaire non créé en base — incohérence de données
        if ($partner === null) {
            return $this->forbid($request, 'Compte partenaire introuvable.');
        }

        // Partenaire suspendu ou inactif — bloqué immédiatement
        if ($partner->status !== PartnerStatus::Active) {
            return $this->forbid(
                $request,
                'Votre compte partenaire est '.$partner->status->value.'.',
                ['status' => $partner->status->value],
            );
        }

        // Partage du Partner résolu dans les attributes de la requête
        // Accessible dans le Controller via : $request->attributes->get('partner')
        $request->attributes->set('partner', $partner);

        return $next($request);
    }

    /**
     * Redirige vers /pwa/login (web) ou renvoie 401 JSON (API).
     */
    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(
                ['error' => 'Non authentifié.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return redirect()
            ->guest(route('pwa.login'))
            ->withErrors(['email' => 'Veuillez vous connecter pour continuer.']);
    }

    /**
     * 403 web (redirect + flash) ou 403 JSON (API).
     *
     * @param  array<string, mixed>  $extra
     */
    private function forbid(Request $request, string $message, array $extra = []): Response
    {
        if ($request->expectsJson()) {
            return response()->json(
                array_merge(['error' => $message], $extra),
                Response::HTTP_FORBIDDEN,
            );
        }

        // Ctx web : logout éventuel puis retour login avec erreur en flash.
        // On garde l'utilisateur connecté en session web tant qu'il a un rôle
        // valide côté User ; c'est le Partner qui est inactif, pas lui.
        return redirect()
            ->route('pwa.login')
            ->withErrors(['email' => $message]);
    }
}
