<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DashboardServiceInterface;
use App\Enums\OrderStatus;
use App\Enums\PartnerStatus;
use App\Enums\QrStatus;
use App\Models\Order;
use App\Models\Partner;
use App\Models\QrCode;
use App\Models\Validation;
use App\Services\Concerns\InteractsWithCache;
use App\Services\Concerns\LogsServiceActivity;
use App\Services\Support\DashboardKpis;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service de tableau de bord administrateur.
 *
 * Agrège les KPIs depuis MySQL et les cache via Redis avec TTL modulables.
 * Chaque méthode publique encapsule son propre cache key → invalidation fine.
 *
 * Stratégie d'invalidation :
 *  - kpis()           : TTL 60s, pas d'invalidation manuelle (acceptable)
 *  - partnerStats()   : TTL 300s, invalidée après chaque validation du partenaire
 *  - alerts           : TTL 30s, calculées en même temps que les KPIs
 *
 * Pourquoi cache + non-query : la table `validations` peut atteindre
 * des millions de lignes en production. Sans cache, COUNT(*) devient lent.
 */
final class DashboardService implements DashboardServiceInterface
{
    use InteractsWithCache, LogsServiceActivity;

    /** TTL par méthode (secondes) — surchargeable par config() plus tard */
    private const TTL_KPIS = 60;

    private const TTL_PARTNER_STATS = 300;

    private const TTL_ALERTS = 30;

    public function __construct()
    {
        $this->cachePrefix = 'dashboard';
        $this->initializeCache(Cache::store());
    }

    // -------------------------------------------------------------------------
    // KPIs principaux
    // -------------------------------------------------------------------------

    public function kpis(): DashboardKpis
    {
        $key = 'kpis:'.CarbonImmutable::today()->toDateString();

        return $this->remember($key, self::TTL_KPIS, function (): DashboardKpis {
            $today = CarbonImmutable::today();

            // Requêtes optimisées : chaque COUNT utilise les index définis dans
            // la migration (cf. SPECS §12.1).
            $qrGeneratedToday = QrCode::whereDate('created_at', $today)->count();
            $qrUsedToday = QrCode::where('status', QrStatus::Used)
                ->whereDate('used_at', $today)
                ->count();
            $qrExpiredUnscanned = QrCode::expiredUnscanned()->count();

            // Commandes payées sans QR Code lié (jobs en échec probable).
            // subSelect pour éviter une jointure lourde.
            $ordersAwaitingQr = Order::where('status', OrderStatus::Paid)
                ->whereDoesntHave('qrCodes', function ($q): void {
                    $q->whereNotIn('status', [QrStatus::Revoked->value]);
                })
                ->count();

            // Taux d'utilisation global (%)
            $totalQr = QrCode::count();
            $totalUsed = QrCode::where('status', QrStatus::Used)->count();
            $usageRate = $totalQr > 0 ? ($totalUsed / $totalQr) * 100 : 0.0;

            // Temps moyen de livraison email (Order.created_at → QrCode.created_at)
            // AVG de TIMESTAMPDIFF en SQL pour éviter le transfert de données.
            $avgDeliverySeconds = (int) DB::table('orders')
                ->join('qr_codes', 'qr_codes.order_id', '=', 'orders.id')
                ->whereNotNull('qr_codes.created_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, orders.created_at, qr_codes.created_at)) as avg_secs')
                ->value('avg_secs');

            // Alertes évaluées à la demande.
            $alerts = $this->computeAlerts($qrExpiredUnscanned);

            return new DashboardKpis(
                qrGeneratedToday: $qrGeneratedToday,
                qrUsedToday: $qrUsedToday,
                qrExpiredUnscanned: $qrExpiredUnscanned,
                ordersAwaitingQr: $ordersAwaitingQr,
                usageRate: (float) $usageRate,
                avgEmailDeliverySeconds: $avgDeliverySeconds !== null ? (int) $avgDeliverySeconds : null,
                alerts: $alerts,
                computedAt: CarbonImmutable::now(),
            );
        });
    }

    // -------------------------------------------------------------------------
    // Stats partenaire
    // -------------------------------------------------------------------------

    public function partnerStats(Partner $partner): array
    {
        $key = "partner:stats:{$partner->id}";

        return $this->remember($key, self::TTL_PARTNER_STATS, function () use ($partner): array {
            $start = CarbonImmutable::now()->subDays(7);

            // Groupe par jour pour afficher une série temporelle.
            // Utilise les index idx_valid_partner_date (partner_id, scanned_at).
            $daily = Validation::query()
                ->where('partner_id', $partner->id)
                ->where('scanned_at', '>=', $start)
                ->selectRaw('DATE(scanned_at) as day, COUNT(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->pluck('total', 'day')
                ->toArray();

            $totalValid = Validation::query()
                ->where('partner_id', $partner->id)
                ->where('scanned_at', '>=', $start)
                ->where('status', 'valid')
                ->count();

            $totalFailed = Validation::query()
                ->where('partner_id', $partner->id)
                ->where('scanned_at', '>=', $start)
                ->where('status', 'failed')
                ->count();

            return [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
                'period' => [
                    'start' => $start->toDateString(),
                    'end' => CarbonImmutable::now()->toDateString(),
                ],
                'totals' => [
                    'valid' => $totalValid,
                    'failed' => $totalFailed,
                    'success_rate' => ($totalValid + $totalFailed) > 0
                        ? round(($totalValid / ($totalValid + $totalFailed)) * 100, 2)
                        : 0.0,
                ],
                'daily_breakdown' => $daily,
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Invalidation de cache
    // -------------------------------------------------------------------------

    public function invalidateAfterValidation(?int $partnerId = null): void
    {
        // KPIs quotidiennes potentiellement impactées (utilisation globale)
        $this->forget('kpis:'.CarbonImmutable::today()->toDateString());

        // Stats du partenaire spécifique
        if ($partnerId !== null) {
            $this->forget("partner:stats:{$partnerId}");
        }
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    /**
     * Évalue les conditions d'alerte selon SPECS §11.2.
     *
     * @return array<int, array{level: string, message: string, count: int|null}>
     */
    private function computeAlerts(int $qrExpiredUnscanned): array
    {
        $alerts = [];

        // Rouge : jobs en échec — non calculé ici (source = failed_jobs).
        // Le dashboard controller peut compléter.

        // Orange : QR expirés non scannés au-dessus du seuil.
        $threshold = (int) config('qr.alert_threshold', 10);

        if ($qrExpiredUnscanned > $threshold) {
            $alerts[] = [
                'level' => 'orange',
                'message' => "{$qrExpiredUnscanned} QR Codes expirés non scannés.",
                'count' => $qrExpiredUnscanned,
            ];
        }

        // Jaune : partenaire inactif depuis > 48h.
        $stalePartners = Partner::where('status', PartnerStatus::Active)
            ->whereDoesntHave('validations', function ($q): void {
                $q->where('scanned_at', '>=', CarbonImmutable::now()->subHours(48));
            })
            ->count();

        if ($stalePartners > 0) {
            $alerts[] = [
                'level' => 'yellow',
                'message' => "{$stalePartners} partenaire(s) sans scan depuis plus de 48h.",
                'count' => $stalePartners,
            ];
        }

        return $alerts;
    }
}
