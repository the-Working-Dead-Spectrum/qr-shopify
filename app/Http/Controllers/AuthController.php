<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\PartnerResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Authentification admin (web) et partenaire (API).
 *
 * Stratégie différenciée (cf. SPECS §6.5) :
 *  - Admin : session web Laravel classique, redirection vers /admin/dashboard
 *  - Partenaire : token Sanctum Bearer (généré à la création par l'admin)
 *
 * Le login partenaire n'est PAS un endpoint publique — il sert uniquement
 * à régénérer un token en cas de perte (avec vérification email).
 *
 * Endpoints :
 *  - GET  /login      : formulaire de login admin (vue)
 *  - POST /login      : authentification admin (session)
 *  - POST /logout     : déconnexion admin (invalide la session)
 *  - POST /api/login  : login partenaire par token Sanctum (recovery)
 */
final class AuthController extends Controller
{
    /**
     * Affiche le formulaire de login admin.
     * GET /login
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Traite le login admin.
     * POST /login
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // On log l'échec SANS le password (sécurité)
            Log::warning('[auth] login_failed', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides.',
            ]);
        }

        $user = Auth::user();

        // Vérification du rôle : seul un admin peut accéder à /admin
        if (! $user->isAdmin()) {
            Auth::logout();
            Log::warning('[auth] non_admin_login_attempt', [
                'email' => $user->email,
                'role' => $user->role->value,
            ]);

            throw ValidationException::withMessages([
                'email' => 'Accès non autorisé.',
            ]);
        }

        // Régénération de session (protection fixation de session)
        $request->session()->regenerate();

        Log::info('[auth] admin_login_success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Déconnexion admin.
     * POST /logout
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('[auth] admin_logout', ['user_id' => $userId]);

        return redirect()->route('login');
    }

    /**
     * Login partenaire par Sanctum token (pour récupération / multi-device).
     * POST /api/auth/login
     *
     * Body : { "email": "...", "device_name": "iPhone 12 - Marie" }
     * Retourne : nouveau token Sanctum
     *
     * ⚠️ Cet endpoint permet de générer un NOUVEAU token à partir des credentials.
     * À utiliser avec parcimonie : un partenaire ne devrait pas avoir à se loguer
     * régulièrement (le token est généré à la création et envoyé une seule fois).
     */
    public function apiLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'], // Option : password défini à la création
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user === null || ! $user->isPartner() || ! Hash::check($data['password'], $user->password)) {
            // Délai constant pour éviter timing attack
            usleep(300_000);

            Log::warning('[auth] partner_login_failed', [
                'email' => $data['email'],
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Identifiants invalides.'], 401);
        }

        // Révoque les anciens tokens pour ce device pour éviter accumulation.
        $user->tokens()->where('name', $data['device_name'])->delete();

        // Création du nouveau token
        $token = $user->createToken($data['device_name'], ['scan:qr']);

        Log::info('[auth] partner_login_success', [
            'user_id' => $user->id,
            'device_name' => $data['device_name'],
        ]);

        return response()->json([
            'token' => $token->plainTextToken,
            'partner' => new PartnerResource(
                $user->partner()->with('user')->first(),
            ),
        ]);
    }

    /**
     * Déconnexion partenaire (révocation token Sanctum courant).
     * POST /api/auth/logout
     */
    public function apiLogout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }

        return response()->json(['message' => 'Déconnecté.']);
    }
}
