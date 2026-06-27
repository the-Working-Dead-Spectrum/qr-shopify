<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pwa;

use App\Enums\PartnerStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\PwaLoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Authentification de la PWA Partenaire (côté web).
 *
 * Modèle hybride :
 *  - Navigation entre pages  → session web Laravel (cookie)
 *  - Appels API critiques    → token Sanctum Bearer (stocké en localStorage
 *                              côté front, posé via <meta name="api-token">
 *                              après login pour éviter tout flash visible)
 *
 * Pourquoi ce choix :
 *  La session web seule ne suffit pas, car les endpoints /api/* (validate,
 *  validations/my, partner/me) sont protégés par auth:sanctum. Sanctum
 *  accepte le cookie session si SANCTUM_STATEFUL_DOMAINS inclut le host,
 *  mais on garde le Bearer pour rester aligné avec l'usage mobile et
 *  permettre une future app native sans réécrire le front.
 *
 * Endpoints :
 *  - GET  /pwa/login  : formulaire (vue Blade)
 *  - POST /pwa/login  : authentification web + émission token Sanctum
 *  - POST /pwa/logout : déconnexion (session + révocation tokens)
 */
final class PwaAuthController extends Controller
{
    /**
     * Traite le login partenaire.
     * POST /pwa/login
     *
     * Flow :
     *  1. Auth::guard('web')->attempt() — vérifie credentials
     *  2. Vérifie rôle = partner + Partner.status = Active
     *  3. Régénère la session (anti-fixation)
     *  4. Génère un token Sanctum (pour les appels /api/*)
     *  5. Stocke le token en session flash (consommé par la vue login)
     *  6. Redirige vers /pwa/scan
     */
    public function login(PwaLoginRequest $request): RedirectResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (! Auth::guard('web')->attempt($credentials, false)) {
            Log::warning('[pwa.auth] login_failed', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides.',
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        // Vérification du rôle — seuls les partenaires peuvent entrer en PWA
        if ($user->role !== Role::Partner) {
            Auth::guard('web')->logout();

            Log::warning('[pwa.auth] non_partner_login_attempt', [
                'email' => $user->email,
                'role' => $user->role->value,
            ]);

            throw ValidationException::withMessages([
                'email' => 'Accès réservé aux partenaires.',
            ]);
        }

        // Vérification du statut Partner — suspendu = bloqué
        $partner = $user->partner;

        if ($partner === null || $partner->status !== PartnerStatus::Active) {
            Auth::guard('web')->logout();

            Log::warning('[pwa.auth] inactive_partner_login_attempt', [
                'email' => $user->email,
                'partner_id' => $partner?->id,
                'status' => $partner?->status?->value,
            ]);

            throw ValidationException::withMessages([
                'email' => $partner === null
                    ? 'Aucun compte partenaire associé à cet email.'
                    : 'Votre compte partenaire est '.$partner->status->value.'.',
            ]);
        }

        // Anti-fixation de session : on régénère l'ID de session.
        $request->session()->regenerate();

        // Génération du token Sanctum pour les futurs appels API.
        // Capacité 'scan:qr' — déjà utilisée par la ValidationController.
        $deviceName = 'pwa-'.substr(hash('sha256', $request->userAgent() ?? 'unknown'), 0, 12);
        $token = $user->createToken($deviceName, ['scan:qr']);

        // Flash du token pour la vue suivante. La vue /pwa/scan le lira
        // via session('api_token') et le posera dans <meta name="api-token">.
        // Pas de stockage persistant côté serveur : la régénération se fait
        // à chaque login, conformément au principe de moindre privilège.
        $request->session()->put('pwa.api_token', $token->plainTextToken);

        Log::info('[pwa.auth] login_success', [
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'device_name' => $deviceName,
            'ip' => $request->ip(),
        ]);

        return redirect()->intended(route('pwa.scan'));
    }

    /**
     * Déconnexion partenaire.
     * POST /pwa/logout
     *
     *  - Révoque tous les tokens Sanctum actifs du partenaire (multi-device)
     *  - Détruit la session web
     *
     * On révoque TOUS les tokens et pas seulement le courant, car la PWA
     * n'expose pas de gestion fine et un partenaire "se déconnecte" =
     * nettoyage complet de son empreinte numérique.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        $partnerId = $user?->partner?->id;

        if ($user !== null) {
            $user->tokens()->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('[pwa.auth] logout', [
            'user_id' => $user?->id,
            'partner_id' => $partnerId,
        ]);

        return redirect()->route('pwa.login');
    }
}
