<?php

declare(strict_types=1);

namespace App\Services\Support;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;

/**
 * KPIs agrégés pour le dashboard administrateur.
 *
 * Immutable : toutes les propriétés sont readonly. Mis en cache tel quel
 * via `Cache::remember()` — pas besoin de re-sérialisation.
 *
 * Les compteurs sont pré-calculés par DashboardService pour éviter que
 * chaque vue (Blade, JSON, API) refasse les requêtes.
 */
final readonly class DashboardKpis implements Arrayable
{
    public function __construct(
        // Volumes
        public int $qrGeneratedToday,
        public int $qrUsedToday,
        public int $qrExpiredUnscanned,
        public int $ordersAwaitingQr,
        // Taux (en pourcentage, valeur 0..100)
        public float $usageRate,
        // Performance
        public ?int $avgEmailDeliverySeconds,
        // Alertes (déjà seuillées)
        public array $alerts,
        // Métadonnées de fraîcheur
        public DateTimeInterface $computedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'qr_generated_today' => $this->qrGeneratedToday,
            'qr_used_today' => $this->qrUsedToday,
            'qr_expired_unscanned' => $this->qrExpiredUnscanned,
            'orders_awaiting_qr' => $this->ordersAwaitingQr,
            'usage_rate' => round($this->usageRate, 2),
            'avg_email_delivery_secs' => $this->avgEmailDeliverySeconds,
            'alerts' => $this->alerts,
            'computed_at' => $this->computedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
