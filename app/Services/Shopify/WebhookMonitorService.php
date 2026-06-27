<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Order;
use App\Models\ShopifyWebhookEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Service de monitoring des webhooks Shopify.
 *
 * Calcule les KPIs pour le dashboard admin :
 *  - webhooks reçus / traités / échoués
 *  - rejets (HMAC invalide, replay, IP)
 *  - temps moyen de traitement
 *  - santé des topics
 *
 * Les calculs sont mis en cache 60 secondes pour éviter de scanner
 * la table shopify_webhook_events à chaque requête dashboard.
 */
final class WebhookMonitorService
{
    private const CACHE_TTL_SECONDS = 60;

    /**
     * KPIs des dernières 24 heures.
     *
     * @return array<string, mixed>
     */
    public function last24hStats(): array
    {
        return $this->cached('shopify:webhook:stats:24h', function (): array {
            $since = CarbonImmutable::now()->subDay();

            return [
                'received' => $this->countSince('received', $since),
                'processed' => $this->countSince('processed', $since),
                'failed' => $this->countSince('failed', $since),
                'skipped' => $this->countSince('skipped', $since),
                'by_topic' => $this->countByTopicSince($since),
                'avg_latency_ms' => $this->avgLatencySince($since),
            ];
        });
    }

    /**
     * KPIs des 7 derniers jours.
     *
     * @return array<string, mixed>
     */
    public function last7dStats(): array
    {
        return $this->cached('shopify:webhook:stats:7d', function (): array {
            $since = CarbonImmutable::now()->subDays(7);

            return [
                'received' => $this->countSince('received', $since),
                'processed' => $this->countSince('processed', $since),
                'failed' => $this->countSince('failed', $since),
                'success_rate' => $this->successRateSince($since),
                'top_failures' => $this->topFailuresSince($since, limit: 5),
            ];
        });
    }

    /**
     * État de la queue Shopify (jobs échoués).
     *
     * @return array<string, mixed>
     */
    public function queueHealth(): array
    {
        return [
            'failed_jobs_count' => DB::table('failed_jobs')
                ->where('queue', 'default')
                ->where('created_at', '>=', CarbonImmutable::now()->subDays(7))
                ->count(),
            'last_failure_at' => DB::table('failed_jobs')
                ->where('queue', 'default')
                ->orderByDesc('created_at')
                ->value('created_at'),
        ];
    }

    /**
     * État de la synchronisation des commandes.
     *
     * @return array<string, mixed>
     */
    public function ordersSyncHealth(): array
    {
        $since = CarbonImmutable::now()->subDay();

        return [
            'orders_created_24h' => Order::where('created_at', '>=', $since)->count(),
            'orders_paid_24h' => Order::paid()->where('created_at', '>=', $since)->count(),
            'orders_cancelled_24h' => Order::where('status', 'cancelled')
                ->where('updated_at', '>=', $since)
                ->count(),
        ];
    }

    /**
     * Nettoie le cache du monitoring (utile pour les tests et debug).
     */
    public function flushCache(): void
    {
        cache()->forget('shopify:webhook:stats:24h');
        cache()->forget('shopify:webhook:stats:7d');
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function countSince(string $status, CarbonInterface $since): int
    {
        return ShopifyWebhookEvent::query()
            ->where('status', $status)
            ->where('received_at', '>=', $since)
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function countByTopicSince(CarbonInterface $since): array
    {
        return ShopifyWebhookEvent::query()
            ->where('received_at', '>=', $since)
            ->groupBy('topic')
            ->selectRaw('topic, COUNT(*) as count')
            ->pluck('count', 'topic')
            ->toArray();
    }

    private function avgLatencySince(CarbonInterface $since): int
    {
        // Calcul à partir de processed_at - received_at en secondes * 1000
        $avg = ShopifyWebhookEvent::query()
            ->where('status', 'processed')
            ->where('received_at', '>=', $since)
            ->whereNotNull('processed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MICROSECOND, received_at, processed_at) / 1000) as avg_ms')
            ->value('avg_ms');

        return (int) ($avg ?? 0);
    }

    private function successRateSince(CarbonInterface $since): float
    {
        $total = ShopifyWebhookEvent::query()
            ->where('received_at', '>=', $since)
            ->count();

        if ($total === 0) {
            return 100.0;
        }

        $processed = ShopifyWebhookEvent::query()
            ->where('status', 'processed')
            ->where('received_at', '>=', $since)
            ->count();

        return round(($processed / $total) * 100, 2);
    }

    /**
     * @return array<int, array{topic: string, count: int}>
     */
    private function topFailuresSince(CarbonInterface $since, int $limit): array
    {
        return ShopifyWebhookEvent::query()
            ->where('status', 'failed')
            ->where('received_at', '>=', $since)
            ->groupBy('topic')
            ->selectRaw('topic, COUNT(*) as count')
            ->orderByDesc('count')
            ->limit($limit)
            ->get(['topic', 'count'])
            ->map(fn ($r) => ['topic' => $r->topic, 'count' => (int) $r->count])
            ->toArray();
    }

    /**
     * Cache générique avec gestion d'erreur (cache DB peut être désactivé).
     *
     * @return array<string, mixed>
     */
    private function cached(string $key, callable $callback): array
    {
        try {
            return cache()->remember($key, self::CACHE_TTL_SECONDS, $callback);
        } catch (Throwable) {
            return $callback();
        }
    }
}
