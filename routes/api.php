<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Api\ValidationController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — versionnées via préfixe /api/v1 (futur-proof)
|--------------------------------------------------------------------------
|
| Note : pour ce projet, /api/v1 est optionnel. On utilise /api/* simple.
| Middleware global déjà appliqué dans bootstrap/app.php :
|   - statefulApi() : Sanctum
|   - throttleWithRedis() : rate limiting
*/

// ---------------------------------------------------------------------------
// Authentification — refresh token (session web requise)
// ---------------------------------------------------------------------------
// Le login principal passe par POST /pwa/login (PwaAuthController).
// Cet endpoint régénère un token Sanctum pour un partenaire déjà authentifié.
Route::middleware(['auth:web', 'ensure.partner', 'throttle:api'])
    ->prefix('auth')
    ->group(function (): void {
        Route::post('/refresh-token', [\App\Http\Controllers\Api\AuthController::class, 'refreshToken'])
            ->name('api.auth.refresh-token');
    });

// ---------------------------------------------------------------------------
// Endpoints partenaires (Sanctum + EnsurePartner + throttle)
// ---------------------------------------------------------------------------
Route::middleware(['auth:sanctum', 'ensure.partner', 'throttle:api'])
    ->group(function (): void {

        // Validation QR (endpoint critique)
        Route::post('/validate', [ValidationController::class, 'validate'])
            ->name('api.validate');

        // Historique + profil
        Route::get('/validations/my', [\App\Http\Controllers\Api\AuthController::class, 'myValidations'])
            ->name('api.validations.my');

        Route::get('/partner/me', [\App\Http\Controllers\Api\AuthController::class, 'me'])
            ->name('api.partner.me');

        // Déconnexion (Sanctum uniquement — révoque le token courant)
        Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])
            ->name('api.auth.logout');
    });

// ---------------------------------------------------------------------------
// Endpoints QR (auth:sanctum seul — ouvert aux partenaires ET admin)
// ---------------------------------------------------------------------------
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/qr/{uuid}', [QrCodeController::class, 'show'])
        ->name('api.qr.show');

    Route::get('/qr/{uuid}/download', [QrCodeController::class, 'download'])
        ->name('api.qr.download');
});

// ---------------------------------------------------------------------------
// Régénération QR (admin via session web)
// ---------------------------------------------------------------------------
// Action sensible : déclenche une révocation + nouveau QR + email au client.
// Protégée par auth:web + ensure.admin (même niveau que le dashboard Blade).
Route::middleware(['auth:web', 'ensure.admin', 'throttle:api'])
    ->group(function (): void {
        Route::post('/orders/{order}/qr/regenerate', [QrCodeController::class, 'regenerate'])
            ->name('api.qr.regenerate');
    });

// ---------------------------------------------------------------------------
// Endpoints admin (utilisent la session web via auth:web)
// ---------------------------------------------------------------------------
Route::middleware(['auth:web', 'ensure.admin', 'throttle:api'])
    ->prefix('admin')
    ->group(function (): void {
        Route::get('/dashboard', [AdminController::class, 'dashboardJson'])
            ->name('api.admin.dashboard');
    });

// ---------------------------------------------------------------------------
// Gestion des tokens partenaires (API pour l'interface admin)
// ---------------------------------------------------------------------------
Route::middleware(['auth:web', 'ensure.admin', 'throttle:api'])
    ->prefix('partners/{partner}')
    ->group(function (): void {
        Route::post('/tokens', [\App\Http\Controllers\Api\PartnerTokenController::class, 'store'])
            ->name('api.partners.tokens.store');
        
        Route::delete('/tokens/{tokenId}', [\App\Http\Controllers\Api\PartnerTokenController::class, 'destroy'])
            ->name('api.partners.tokens.destroy');
    });