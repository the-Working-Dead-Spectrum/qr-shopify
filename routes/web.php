<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Webhook\ShopifyWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Page d'accueil
Route::get('/', function () {
    return view('welcome');
})->name('home');

// ---------------------------------------------------------------------------
// Page publique QR (rendue HTML pour fallback email)
// GET /qr/{uuid}
// ---------------------------------------------------------------------------
Route::get('/qr/{uuid}', [QrCodeController::class, 'publicPage'])
    ->name('qr.show')
    ->where('uuid', '[a-f0-9]{64}');

// ---------------------------------------------------------------------------
// Authentification admin (sessions web)
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1'); // 6 tentatives/min
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:web')
    ->name('logout');

// ---------------------------------------------------------------------------
// Webhooks Shopify (HMAC vérifié en amont — pas d'auth)
//
// 7 endpoints supportés (cf. config/shopify.php) :
//   - orders/create        → création commande (panier)
//   - orders/paid          → déclenche génération QR
//   - orders/updated       → mise à jour informations
//   - orders/cancelled     → annulation + révocation QR
//   - orders/delete        → suppression (GDPR-like)
//   - refunds/create       → remboursement + révocation QR
//   - app/uninstalled      → désinstallation (alerte admin)
//
// Sécurité :
//   - shopify.hmac    : validation HMAC + protection replay
//   - shopify.ip      : vérification IP Shopify (optionnelle via config)
//   - throttle:webhook: rate limit dédié
// ---------------------------------------------------------------------------
Route::prefix('webhooks/shopify')
    ->middleware(['shopify.hmac', 'shopify.ip', 'throttle:webhook'])
    ->group(function (): void {
        Route::post('/order-created', [ShopifyWebhookController::class, 'handleOrderCreated'])
            ->name('webhooks.shopify.order-created');

        Route::post('/order-paid', [ShopifyWebhookController::class, 'handleOrderPaid'])
            ->name('webhooks.shopify.order-paid');

        Route::post('/order-updated', [ShopifyWebhookController::class, 'handleOrderUpdated'])
            ->name('webhooks.shopify.order-updated');

        Route::post('/order-cancelled', [ShopifyWebhookController::class, 'handleOrderCancelled'])
            ->name('webhooks.shopify.order-cancelled');

        Route::post('/order-deleted', [ShopifyWebhookController::class, 'handleOrderDeleted'])
            ->name('webhooks.shopify.order-deleted');

        Route::post('/refund-created', [ShopifyWebhookController::class, 'handleRefundCreated'])
            ->name('webhooks.shopify.refund-created');

        Route::post('/app-uninstalled', [ShopifyWebhookController::class, 'handleAppUninstalled'])
            ->name('webhooks.shopify.app-uninstalled');
    });

// ---------------------------------------------------------------------------
// Routes admin (auth:web + rôle admin)
// ---------------------------------------------------------------------------
Route::middleware(['auth:web', 'ensure.admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {

        // Dashboard
        Route::get('/', [AdminController::class, 'dashboardNew'])->name('dashboard');
        Route::get('/dashboard', [AdminController::class, 'dashboardNew'])->name('dashboard.alias');
        Route::get('/dashboard-old', [AdminController::class, 'dashboard'])->name('dashboard.old');

        // Orders
        Route::get('/orders', [AdminController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{order}', [AdminController::class, 'showOrder'])->name('orders.show');
        Route::post('/orders/{order}/resend-qr', [AdminController::class, 'resendQr'])
            ->name('orders.resend-qr');

        // QR actions
        Route::post('/qr/{qr}/revoke', [AdminController::class, 'revokeQr'])->name('qr.revoke');

        // Partners
        Route::get('/partners', [AdminController::class, 'partners'])->name('partners.index');
        Route::get('/partners/{partner}', [AdminController::class, 'showPartner'])->name('partners.show');
        Route::post('/partners', [AdminController::class, 'storePartner'])->name('partners.store');
        Route::patch('/partners/{partner}', [AdminController::class, 'updatePartner'])->name('partners.update');
        Route::delete('/partners/{partner}/tokens/{tokenId}', [AdminController::class, 'revokePartnerToken'])
            ->name('partners.tokens.revoke');

        // Validations
        Route::get('/validations', [AdminController::class, 'validations'])->name('validations.index');

        // Exports
        Route::get('/reports/export', [AdminController::class, 'exportCsv'])->name('reports.export');
        
        // Settings
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::post('/settings/test-shopify-connection', [AdminController::class, 'testShopifyConnection'])->name('settings.test-shopify-connection');
        Route::get('/settings/shopify-sync-status', [AdminController::class, 'getShopifySyncStatus'])->name('settings.shopify-sync-status');
        
        // Route temporaire pour tester la vue système
        Route::get('/test-system-view', function () {
            return view('admin.system_settings');
        })->name('test.system.view');
        
        // Logs
        Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
        
        // Reports page
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        
        // Support
        Route::get('/support', [AdminController::class, 'support'])->name('support');
        Route::post('/support', [AdminController::class, 'sendSupportRequest'])->name('support.send');

        // API pour données en temps réel
        Route::get('/api/dashboard-data', [AdminController::class, 'getRealTimeData'])->name('api.dashboard-data');

        // Shopify Dashboard
        Route::prefix('shopify')->name('shopify.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Admin\ShopifyDashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/stats', [\App\Http\Controllers\Admin\ShopifyDashboardController::class, 'statsJson'])
                ->name('stats.json');

            Route::post('/test-connection', [\App\Http\Controllers\Admin\ShopifyDashboardController::class, 'testConnection'])
                ->name('test-connection');
        });
    });

// ---------------------------------------------------------------------------
// PWA Partenaire (auth: web + ensure.partner)
// ---------------------------------------------------------------------------
// La navigation PWA utilise la session web Laravel (cookie) ; les appels
// API internes (/api/validate, /api/partner/me, etc.) utilisent un token
// Sanctum Bearer stocké dans <meta name="api-token"> après login.
// Voir App\Http\Controllers\Pwa\PwaAuthController pour le flux complet.
Route::prefix('pwa')->middleware(['web', 'pwa.headers'])->group(function (): void {
    // Page de login PWA — accessible uniquement aux invités
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', function () {
            return view('pwa.login');
        })->name('pwa.login');

        Route::post('/login', [\App\Http\Controllers\Pwa\PwaAuthController::class, 'login'])
            ->middleware('throttle:6,1')
            ->name('pwa.login.attempt');
    });

    // Routes protégées : session web active + rôle/partner actif
    Route::middleware(['auth:web', 'ensure.partner'])->group(function (): void {
        Route::get('/scan', function () {
            return view('pwa.scan');
        })->name('pwa.scan');

        Route::get('/history', function () {
            return view('pwa.history');
        })->name('pwa.history');

        Route::get('/result', function () {
            return view('pwa.result');
        })->name('pwa.result');

        Route::post('/logout', [\App\Http\Controllers\Pwa\PwaAuthController::class, 'logout'])
            ->name('pwa.logout');

        // Retire le token Sanctum de la session après transfert dans localStorage.
        // Endpoint idempotent appelé par pwa-head.blade.php.
        Route::post('/api-token-consume', function (\Illuminate\Http\Request $request) {
            $request->session()->forget('pwa.api_token');
            return response()->noContent();
        })->name('pwa.api-token.consume');
    });
});

// ---------------------------------------------------------------------------
// Redirection legacy / fallback
// ---------------------------------------------------------------------------
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});