<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PartnerStatus;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Endpoints JSON pour la PWA Partenaire.
 *
 * Stratégie d'authentification (cf. SPECS §6.5) :
 *  - Login principal   : formulaire web /pwa/login (PwaAuthController)
 *  - Endpoints ci-dessous : Sanctum Bearer, token généré au login web
 *    et persisté dans localStorage côté front.
 *
 * Cette classe expose uniquement des endpoints consommés via fetch()
 * depuis la PWA (et non des écrans de login).
 */
final class AuthController extends Controller
{
    /**
     * Régénère un token Sanctum pour le partenaire déjà authentifié
     * via la session web. Endpoint utilisé lors d'un changement d'appareil
     * ou d'une réinitialisation après perte.
     *
     * POST /api/auth/refresh-token
     *
     * Body : { "device_name": "iPhone 12 - Marie" }
     * Retourne : nouveau token Sanctum + profil partenaire
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        // La session web doit être active (utilisateur déjà logué via /pwa/login).
        $user = Auth::guard('web')->user();

        if ($user === null) {
            return response()->json(['error' => 'Non authentifié.'], 401);
        }

        $partner = $user->partner;

        if ($partner === null || $partner->status !== PartnerStatus::Active) {
            return response()->json(['error' => 'Compte partenaire inactif.'], 403);
        }

        // Révoque les anciens tokens pour ce device pour éviter accumulation.
        $user->tokens()->where('name', $data['device_name'])->delete();

        // Création du nouveau token
        $token = $user->createToken($data['device_name'], ['scan:qr']);

        return response()->json([
            'token' => $token->plainTextToken,
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $user->email,
                'status' => $partner->status->value,
            ],
        ]);
    }

    /**
     * Déconnexion — révoque le token courant.
     * POST /api/auth/logout
     *
     * Note : la déconnexion complète (destruction session + révocation
     * de tous les tokens) se fait via POST /pwa/logout (PwaAuthController).
     * Cet endpoint ne révoque que le token Sanctum actif — utile pour
     * une API future ou une app native où seule la session Sanctum existe.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $request->user()->currentAccessToken()?->delete();
        }

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Informations du partenaire connecté.
     * GET /api/partner/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $partner = $user?->partner;

        if (! $partner) {
            return response()->json([
                'error' => 'Partenaire non trouvé',
            ], 404);
        }

        return response()->json([
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $user->email,
                'status' => $partner->status->value,
                'created_at' => $partner->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Historique des scans du partenaire.
     * GET /api/validations/my
     */
    public function myValidations(Request $request): JsonResponse
    {
        $partner = $request->user()?->partner;

        if (! $partner) {
            return response()->json([
                'error' => 'Partenaire non trouvé',
            ], 404);
        }

        $validations = $partner->validations()
            ->with(['qrCode.order'])
            ->orderByDesc('scanned_at')
            ->limit(50)
            ->get();

        return response()->json([
            'validations' => $validations->map(function ($validation) {
                return [
                    'id' => $validation->id,
                    'qr_code_id' => $validation->qr_code_id,
                    'status' => $validation->status,
                    'scanned_at' => $validation->scanned_at?->toIso8601String(),
                    'ip_address' => $validation->ip_address,
                    'user_agent' => $validation->user_agent,
                    'order' => $validation->qrCode?->order ? [
                        'id' => $validation->qrCode->order->id,
                        'shopify_order_id' => $validation->qrCode->order->shopify_order_id,
                        'customer_name' => $validation->qrCode->order->customer_name,
                        'amount' => $validation->qrCode->order->formatted_amount,
                    ] : null,
                ];
            }),
        ]);
    }
}
