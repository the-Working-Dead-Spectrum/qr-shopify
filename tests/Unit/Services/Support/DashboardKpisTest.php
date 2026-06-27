<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Support;

use App\Services\Support\DashboardKpis;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests du DTO DashboardKpis.
 *
 * Le DTO est sérialisé en cache : il doit être arrayable stable
 * (mêmes clés pour le même état, même après round-trip JSON).
 */
final class DashboardKpisTest extends TestCase
{
    #[Test]
    public function it_serializes_all_fields_with_expected_keys(): void
    {
        $kpis = new DashboardKpis(
            qrGeneratedToday: 10,
            qrUsedToday: 7,
            qrExpiredUnscanned: 3,
            ordersAwaitingQr: 1,
            usageRate: 70.0,
            avgEmailDeliverySeconds: 45,
            alerts: [['level' => 'orange', 'message' => 'x', 'count' => 3]],
            computedAt: CarbonImmutable::parse('2026-06-26 10:00:00'),
        );

        $array = $kpis->toArray();

        $this->assertSame(10, $array['qr_generated_today']);
        $this->assertSame(7, $array['qr_used_today']);
        $this->assertSame(3, $array['qr_expired_unscanned']);
        $this->assertSame(1, $array['orders_awaiting_qr']);
        $this->assertSame(70.0, $array['usage_rate']);
        $this->assertSame(45, $array['avg_email_delivery_secs']);
        $this->assertNotEmpty($array['alerts']);
        $this->assertSame('2026-06-26T10:00:00+00:00', $array['computed_at']);
    }

    #[Test]
    public function it_rounds_usage_rate_to_two_decimals(): void
    {
        $kpis = new DashboardKpis(
            qrGeneratedToday: 0,
            qrUsedToday: 0,
            qrExpiredUnscanned: 0,
            ordersAwaitingQr: 0,
            usageRate: 33.333333,
            avgEmailDeliverySeconds: null,
            alerts: [],
            computedAt: CarbonImmutable::now(),
        );

        $array = $kpis->toArray();

        $this->assertSame(33.33, $array['usage_rate']);
    }

    #[Test]
    public function it_allows_null_avg_delivery(): void
    {
        $kpis = $this->makeKpis(['avgEmailDeliverySeconds' => null]);

        $array = $kpis->toArray();

        $this->assertNull($array['avg_email_delivery_secs']);
    }

    #[Test]
    public function it_produces_empty_alerts_when_none(): void
    {
        $kpis = $this->makeKpis(['alerts' => []]);

        $array = $kpis->toArray();

        $this->assertSame([], $array['alerts']);
    }

    #[Test]
    public function it_survives_cache_round_trip(): void
    {
        // Reproduit le pattern Cache::remember() : serialize / unserialize.
        $kpis = $this->makeKpis();
        $serialized = serialize($kpis);
        /** @var DashboardKpis $restored */
        $restored = unserialize($serialized);

        $this->assertEquals($kpis->toArray(), $restored->toArray());
    }

    // -------------------------------------------------------------------------
    private function makeKpis(array $overrides = []): DashboardKpis
    {
        return new DashboardKpis(
            qrGeneratedToday: $overrides['qrGeneratedToday'] ?? 5,
            qrUsedToday: $overrides['qrUsedToday'] ?? 3,
            qrExpiredUnscanned: $overrides['qrExpiredUnscanned'] ?? 1,
            ordersAwaitingQr: $overrides['ordersAwaitingQr'] ?? 0,
            usageRate: $overrides['usageRate'] ?? 60.0,
            avgEmailDeliverySeconds: $overrides['avgEmailDeliverySeconds'] ?? 30,
            alerts: $overrides['alerts'] ?? [],
            computedAt: $overrides['computedAt'] ?? CarbonImmutable::parse('2026-06-26 10:00:00'),
        );
    }
}