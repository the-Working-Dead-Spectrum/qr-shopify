<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôleur pour la gestion des tokens Sanctum des partenaires.
 * Utilisé par l'interface admin pour créer et révoquer des tokens.
 */
final class PartnerTokenController extends Controller
{
    /**
     * Créer un nouveau token pour un partenaire.
     * POST /api/partners/{partner}/tokens
     */
    public function store(Request $request, Partner $partner): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Vérifier que l'utilisateur authentifié est un admin
        $admin = Auth::user();

        if (! $admin || $admin->role !== Role::Admin) {
            return response()->json([
                'error' => 'Non autorisé',
            ], 403);
        }

        // Créer un nouveau token
        $tokenName = $request->input('name', 'pwa-mobile');
        $token = $partner->user->createToken($tokenName, ['scan:qr']);

        return response()->json([
            'message' => 'Token créé avec succès',
            'token' => $token->plainTextToken,
            'token_id' => $token->accessToken->id,
        ], 201);
    }

    /**
     * Révoquer un token spécifique d'un partenaire.
     * DELETE /api/partners/{partner}/tokens/{tokenId}
     */
    public function destroy(Partner $partner, string $tokenId): JsonResponse
    {
        // Vérifier que l'utilisateur authentifié est un admin
        $admin = Auth::user();

        if (! $admin || $admin->role !== Role::Admin) {
            return response()->json([
                'error' => 'Non autorisé',
            ], 403);
        }

        // Trouver et révoquer le token
        $token = $partner->user->tokens()->find($tokenId);

        if (! $token) {
            return response()->json([
                'error' => 'Token introuvable',
            ], 404);
        }

        $token->delete();

        return response()->json([
            'message' => 'Token révoqué avec succès',
        ]);
    }
}
