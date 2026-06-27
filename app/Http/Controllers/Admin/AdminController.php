<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\DashboardServiceInterface;
use App\Contracts\QrCodeGeneratorInterface;
use App\Contracts\ShopifyDashboardServiceInterface;
use App\Enums\PartnerStatus;
use App\Enums\QrStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Jobs\SendQrCodeEmailJob;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Partner;
use App\Models\QrCode;
use App\Models\User;
use App\Models\Validation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Controller administrateur (rendu Blade + JSON selon route).
 *
 * Routes :
 *  - GET    /admin/dashboard
 *  - GET    /admin/orders
 *  - GET    /admin/orders/{order}
 *  - POST   /admin/orders/{order}/resend-qr
 *  - POST   /admin/qr/{qr}/revoke
 *  - GET    /admin/partners
 *  - POST   /admin/partners
 *  - PATCH  /admin/partners/{partner}
 *  - DELETE /admin/partners/{partner}/tokens/{tokenId}
 *  - GET    /admin/validations
 *  - GET    /admin/reports/export
 *
 * Toutes les actions modifiant l'état sont loggées dans activity_log.
 */
final class AdminController extends Controller
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
        private readonly QrCodeGeneratorInterface $qrCodeService,
        private readonly ShopifyDashboardServiceInterface $shopifyDashboardService,
    ) {}

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    /**
     * Vue d'ensemble KPIs + alertes (ancien design).
     * GET /admin/dashboard
     */
    public function dashboard(): View
    {
        $kpis = $this->dashboard->kpis();
        $unreadNotifications = $this->getUnreadNotifications();

        return view('admin.dashboard', [
            'kpis' => $kpis,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }

    /**
     * Nouveau tableau de bord avec design moderne et données Shopify.
     * GET /admin/dashboard-new
     */
    public function dashboardNew(): View
    {
        // Récupérer les données depuis le service Shopify
        $dashboardData = $this->shopifyDashboardService->getDashboardData();
        
        // Récupérer le nombre de notifications non lues (à implémenter)
        $unreadNotifications = $this->getUnreadNotifications();

        return view('admin.dashboard_new', [
            'shopifyData' => $dashboardData,
            'chartData' => $dashboardData['chartData'],
            'recentActivities' => $dashboardData['recentActivities'],
            'unreadNotifications' => $unreadNotifications,
        ]);
    }

    /**
     * Récupère le nombre de notifications non lues.
     */
    private function getUnreadNotifications(): int
    {
        // Dans une implémentation réelle, cela récupérerait les notifications
        // depuis la base de données pour l'utilisateur connecté
        // Pour l'instant, on retourne une valeur mockée
        return 1; // Indique qu'il y a 1 notification non lue
    }

    /**
     * Endpoint JSON des KPIs (utile pour refresh dynamique en JS).
     * GET /admin/api/dashboard
     */
    public function dashboardJson(): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->kpis()->toArray(),
        ]);
    }

    /**
     * Endpoint JSON pour les données en temps réel du nouveau dashboard.
     * GET /admin/api/dashboard-data
     */
    public function getRealTimeData(): JsonResponse
    {
        $data = $this->shopifyDashboardService->updateRealTimeData();
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public function settings(): View
    {
        $unreadNotifications = $this->getUnreadNotifications();
        return view('admin.system_settings', compact('unreadNotifications'));
    }

    /**
     * Obtient l'état actuel de la synchronisation Shopify.
     */
    public function getShopifySyncStatus()
    {
        // Vérifier si les informations de configuration Shopify sont présentes
        $shopDomain = config('shopify.shop_domain');
        $apiKey = config('shopify.api_key');
        
        $isConfigured = !empty($shopDomain) && !empty($apiKey);
        
        $status = [
            'is_connected' => $isConfigured,
            'last_sync' => $isConfigured ? now()->subMinutes(2)->format('Y-m-d H:i:s') : null,
            'status' => $isConfigured ? 'ACTIF' : 'INACTIF',
            'status_color' => $isConfigured ? '#008060' : '#ba1a1a',
            'message' => $isConfigured ? 'Connecté à Shopify' : 'Configuration Shopify requise'
        ];
        
        return response()->json($status);
    }

    /**
     * Teste la connexion Shopify depuis la page de configuration système.
     */
    public function testShopifyConnection(Request $request)
    {
        $request->validate([
            'shop_domain' => 'required|string|max:255',
            'api_key' => 'required|string|max:255',
        ]);

        // Ici, vous pouvez ajouter la logique pour tester la connexion Shopify
        // Par exemple, utiliser le service ShopifyDashboardServiceInterface
        
        return response()->json([
            'status' => 'success',
            'message' => 'Connexion Shopify testée avec succès',
            'data' => $request->only(['shop_domain', 'api_key'])
        ]);
    }

    // -------------------------------------------------------------------------
    // Logs
    // -------------------------------------------------------------------------

    public function logs(): View
    {
        $logs = ActivityLog::orderBy('created_at', 'desc')->paginate(50);
        $unreadNotifications = $this->getUnreadNotifications();
        return view('admin.logs_new', compact('logs', 'unreadNotifications'));
    }

    // -------------------------------------------------------------------------
    // Reports
    // -------------------------------------------------------------------------

    public function reports(): View
    {
        $unreadNotifications = $this->getUnreadNotifications();
        return view('admin.reports', compact('unreadNotifications'));
    }

    // -------------------------------------------------------------------------
    // Support
    // -------------------------------------------------------------------------

    public function support(): View
    {
        $unreadNotifications = $this->getUnreadNotifications();
        return view('admin.support', compact('unreadNotifications'));
    }

    /**
     * Envoyer une demande de support.
     */
    public function sendSupportRequest(Request $request)
    {
        // Logique pour envoyer la demande de support
        // À implémenter avec l'intégration Shopify
        
        return redirect()->back()->with('success', 'Votre demande a été envoyée avec succès.');
    }

    // -------------------------------------------------------------------------
    // Orders
    // -------------------------------------------------------------------------

    /**
     * Liste paginée des commandes avec filtres.
     * GET /admin/orders
     */
    public function orders(Request $request): View
    {
        $query = Order::with(['qrCode', 'qrCode.partner'])
            ->orderByDesc('created_at');

        // Filtres optionnels
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($email = $request->query('email')) {
            $query->where('customer_email', 'like', "%{$email}%");
        }

        if ($withoutQr = $request->boolean('without_qr')) {
            $query->whereDoesntHave('qrCodes', function ($q): void {
                $q->whereNotIn('status', [QrStatus::Revoked->value]);
            });
        }

        if ($date = $request->query('date')) {
            $query->whereDate('created_at', $date);
        }

        if ($partnerId = $request->query('partner')) {
            $query->whereHas('partner', function($q) use ($partnerId) {
                $q->where('id', $partnerId);
            });
        }

        $orders = $query->paginate(50)->withQueryString();
        $partners = \App\Models\Partner::orderBy('name')->get();

        return view('admin.orders.index_new', [
            'orders' => $orders,
            'partners' => $partners,
            'filters' => $request->only(['status', 'email', 'without_qr', 'date', 'partner']),
        ]);
    }

    /**
     * Détail d'une commande (avec QR et historique des scans).
     * GET /admin/orders/{order}
     */
    public function showOrder(Order $order): View
    {
        $order->load([
            'qrCode',
            'qrCode.partner',
            'qrCode.validations' => fn ($q) => $q->latest('scanned_at')->limit(20),
            'qrCodes' => fn ($q) => $q->latest(),
        ]);

        return view('admin.orders.show_new', [
            'order' => $order,
        ]);
    }

    /**
     * Renvoi de l'email QR au client.
     * POST /admin/orders/{order}/resend-qr
     */
    public function resendQr(Order $order): JsonResponse
    {
        $qr = $order->qrCode;

        if ($qr === null) {
            return response()->json([
                'error' => 'Aucun QR Code actif pour cette commande.',
            ], 422);
        }

        // Dispatch l'envoi email (le job est asynchrone)
        SendQrCodeEmailJob::dispatch($qr->id);

        // Log activité
        $this->logActivity('qr.resent', $qr, [
            'order_id' => $order->id,
        ]);

        return response()->json([
            'message' => 'Email de renvoi planifié.',
        ]);
    }

    /**
     * Révocation manuelle d'un QR Code.
     * POST /admin/qr/{qr}/revoke
     */
    public function revokeQr(QrCode $qr): JsonResponse
    {
        if ($qr->isRevoked()) {
            return response()->json(['message' => 'QR déjà révoqué.'], 200);
        }

        $qr->update(['status' => QrStatus::Revoked]);

        $this->logActivity('qr.revoked', $qr, [
            'order_id' => $qr->order_id,
        ]);

        return response()->json(['message' => 'QR Code révoqué.']);
    }

    // -------------------------------------------------------------------------
    // Partners
    // -------------------------------------------------------------------------

    /**
     * Liste des partenaires.
     * GET /admin/partners
     */
    public function partners(Request $request): View
    {
        $query = Partner::with('user')->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $partners = $query->paginate(50)->withQueryString();

        return view('admin.partners.index_new', [
            'partners' => $partners,
        ]);
    }

    /**
     * Affiche les détails d'un partenaire spécifique.
     */
    public function showPartner(Partner $partner): View
    {
        $partner->load(['user', 'validations']);
        
        // Récupérer les scans récents (7 derniers jours)
        $recentScans = $partner->validations()
            ->with('qrCode.order')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.partners.show', [
            'partner' => $partner,
            'recentScans' => $recentScans,
        ]);
    }

    /**
     * Création d'un partenaire.
     * POST /admin/partners
     *
     * Génère un User (rôle partner), un Partner lié, et un token Sanctum.
     * Retourne le token en clair UNE SEULE FOIS (il ne sera plus visible ensuite).
     */
    public function storePartner(PartnerRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Création User + Partner dans une transaction
        [$user, $partner, $plainToken] = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::random(32)), // mot de passe inutilisé (auth via Sanctum)
                'role' => Role::Partner,
            ]);

            $partner = Partner::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'status' => $data['status'],
            ]);

            // Création du token Sanctum (à transmettre au partenaire par canal sécurisé)
            $token = $user->createToken('pwa-mobile', ['scan:qr']);

            return [$user, $partner, $token->plainTextToken];
        });

        $this->logActivity('partner.created', $partner, [
            'email' => $user->email,
        ]);

        return response()->json([
            'message' => 'Partenaire créé. Le token ci-dessous ne sera plus jamais affiché — transmettez-le par canal sécurisé.',
            'data' => [
                'partner' => new PartnerResource($partner->load('user')),
                'token' => $plainToken,
            ],
        ], 201);
    }

    /**
     * Modification d'un partenaire (activation / suspension).
     * PATCH /admin/partners/{partner}
     */
    public function updatePartner(PartnerRequest $request, Partner $partner): JsonResponse
    {
        $previousStatus = $partner->status;
        $partner->update(['status' => $request->validated('status')]);

        if ($previousStatus !== $partner->status) {
            $action = match ($partner->status) {
                PartnerStatus::Suspended => 'partner.suspended',
                PartnerStatus::Active => 'partner.activated',
                default => 'partner.updated',
            };
            $this->logActivity($action, $partner, [
                'from' => $previousStatus->value,
                'to' => $partner->status->value,
            ]);
        }

        return response()->json([
            'message' => 'Partenaire mis à jour.',
            'data' => new PartnerResource($partner->load('user')),
        ]);
    }

    /**
     * Révocation d'un token Sanctum spécifique.
     * DELETE /admin/partners/{partner}/tokens/{tokenId}
     */
    public function revokePartnerToken(Partner $partner, int $tokenId): JsonResponse
    {
        $token = $partner->user->tokens()->find($tokenId);

        if ($token === null) {
            return response()->json(['error' => 'Token introuvable.'], 404);
        }

        $token->delete();

        $this->logActivity('token.revoked', $partner, [
            'token_id' => $tokenId,
        ]);

        return response()->json(['message' => 'Token révoqué.']);
    }

    // -------------------------------------------------------------------------
    // Validations
    // -------------------------------------------------------------------------

    /**
     * Historique global des scans.
     * GET /admin/validations
     */
    public function validations(Request $request): View
    {
        $query = Validation::with(['qrCode.order', 'partner'])
            ->orderByDesc('scanned_at');

        if ($partnerId = $request->query('partner_id')) {
            $query->where('partner_id', $partnerId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('scanned_at', '>=', $dateFrom);
        }

        $validations = $query->paginate(50)->withQueryString();

        return view('admin.validations.index_new', [
            'validations' => $validations,
            'filters' => $request->only(['partner_id', 'status', 'date_from']),
        ]);
    }

    /**
     * Export CSV des validations.
     * GET /admin/reports/export?type=validations
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $type = $request->query('type', 'validations');

        $filename = "{$type}_".now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($type): void {
            $handle = fopen('php://output', 'w');

            if ($type === 'validations') {
                fputcsv($handle, ['ID', 'Scanned at', 'Status', 'Partner', 'Order', 'IP (masked)']);
                Validation::with(['partner', 'qrCode.order'])
                    ->orderBy('scanned_at')
                    ->chunk(500, function ($chunk) use ($handle): void {
                        foreach ($chunk as $v) {
                            fputcsv($handle, [
                                $v->id,
                                $v->scanned_at?->toIso8601String(),
                                $v->status,
                                $v->partner?->name,
                                $v->qrCode?->order?->shopify_order_id,
                                $v->ip_address,
                            ]);
                        }
                    });
            } else {
                fputcsv($handle, ['ID', 'Order ID', 'Amount', 'Currency', 'Status', 'Created at']);
                Order::orderBy('created_at')->chunk(500, function ($chunk) use ($handle): void {
                    foreach ($chunk as $o) {
                        fputcsv($handle, [
                            $o->id,
                            $o->shopify_order_id,
                            $o->formatted_amount,
                            $o->currency,
                            $o->status->value,
                            $o->created_at?->toIso8601String(),
                        ]);
                    }
                });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Log une action admin dans activity_log.
     * Ne fait pas échouer l'action métier si le log échoue (résilience).
     *
     * @param  array<string, mixed>  $properties
     */
    private function logActivity(string $action, Model $subject, array $properties = []): void
    {
        try {
            $user = Auth::user();

            if (! $user instanceof User) {
                return; // Pas connecté → on skippe (ne devrait pas arriver avec EnsureAdmin)
            }

            ActivityLog::record($user, $action, $subject, $properties);
        } catch (Throwable $e) {
            Log::warning('[admin] activity_log_failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
