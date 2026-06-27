<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Shopify\TestShopifyConnectionJob;
use App\Services\Shopify\WebhookMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard admin pour le module Shopify.
 *
 * Affiche :
 *  - KPIs webhooks (24h / 7j)
 *  - Santé de la queue
 *  - Synchronisation commandes
 *  - Latence moyenne
 *
 * Endpoint JSON `/admin/shopify/stats` (rafraîchi par polling 30s côté Blade).
 */
final class ShopifyDashboardController extends Controller
{
    public function __construct(
        private readonly WebhookMonitorService $monitor,
    ) {}

    /**
     * Page dashboard Shopify.
     */
    public function index(Request $request): View
    {
        $stats24h = $this->monitor->last24hStats();
        $stats7d = $this->monitor->last7dStats();
        $queueHealth = $this->monitor->queueHealth();
        $ordersHealth = $this->monitor->ordersSyncHealth();

        return view('admin.shopify.dashboard', compact(
            'stats24h',
            'stats7d',
            'queueHealth',
            'ordersHealth',
        ));
    }

    /**
     * Endpoint JSON pour refresh asynchrone (polling / XHR).
     */
    public function statsJson(): JsonResponse
    {
        return response()->json([
            'last_24h' => $this->monitor->last24hStats(),
            'last_7d' => $this->monitor->last7dStats(),
            'queue' => $this->monitor->queueHealth(),
            'orders' => $this->monitor->ordersSyncHealth(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Endpoint de test de connexion (dispatche TestShopifyConnectionJob).
     */
    public function testConnection(): JsonResponse
    {
        TestShopifyConnectionJob::dispatch(logPayload: false);

        return response()->json([
            'status' => 'queued',
            'message' => 'Test de connexion Shopify planifié. Vérifiez storage/logs/shopify.log.',
        ]);
    }
}
