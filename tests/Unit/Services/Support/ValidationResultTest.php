<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Support;

use App\Enums\ValidationStatus;
use App\Models\Order;
use App\Models\Partner;
use App\Models\QrCode;
use App\Services\Support\ValidationResult;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests du DTO ValidationResult.
 *
 * Le DTO est immuable : on teste l'instanciation via factories statiques,
 * la sérialisation, et le mapping HTTP (invariant API critique).
 *
 * On étend Tests\TestCase (et non PHPUnit\Framework\TestCase) car les tests
 * construisent des modèles Eloquent (Order, QrCode) qui ont besoin du
 * container Laravel pour résoudre leur connexion DB, même si on ne les
 * persiste pas.
 */
final class ValidationResultTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Factory : valid
    // -------------------------------------------------------------------------

    #[Test]
    public function valid_factory_builds_correct_dto(): void
    {
        $order = new Order(['id' => 42]);
        $qr = $this->makeQr($order, usedAt: '2026-06-26 10:00:00');

        $result = ValidationResult::valid($qr, partnerId: 7);

        $this->assertSame(ValidationStatus::Valid, $result->status);
        $this->assertSame(200, $result->httpCode);
        $this->assertSame(42, $result->orderId);
        $this->assertSame(7, $result->validatedByPartnerId);
        $this->assertSame('QR Code validé avec succès.', $result->message);
        $this->assertTrue($result->isValid());
    }

    // -------------------------------------------------------------------------
    // Factory : refus
    // -------------------------------------------------------------------------

    #[Test]
    public function already_used_returns_409(): void
    {
        $order = new Order(['id' => 1]);
        $qr = $this->makeQr($order, usedAt: '2026-06-25 14:30:00');

        $result = ValidationResult::alreadyUsed($qr);

        $this->assertSame(ValidationStatus::AlreadyUsed, $result->status);
        $this->assertSame(409, $result->httpCode);
        $this->assertSame(1, $result->orderId);
        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function expired_returns_410(): void
    {
        $order = new Order(['id' => 1]);
        $qr = $this->makeQr($order, expiresAt: '2026-06-20 00:00:00');

        $result = ValidationResult::expired($qr);

        $this->assertSame(ValidationStatus::Expired, $result->status);
        $this->assertSame(410, $result->httpCode);
    }

    #[Test]
    public function revoked_returns_403(): void
    {
        $order = new Order(['id' => 1]);
        $qr = $this->makeQr($order);

        $result = ValidationResult::revoked($qr);

        $this->assertSame(ValidationStatus::Revoked, $result->status);
        $this->assertSame(403, $result->httpCode);
    }

    #[Test]
    public function invalid_returns_404_without_qr(): void
    {
        $result = ValidationResult::invalid();

        $this->assertSame(ValidationStatus::Invalid, $result->status);
        $this->assertSame(404, $result->httpCode);
        $this->assertNull($result->orderId);
        $this->assertNull($result->expiresAt);
        $this->assertNull($result->usedAt);
        $this->assertNull($result->validatedByPartnerId);
        $this->assertFalse($result->isValid());
    }

    // -------------------------------------------------------------------------
    // Mapping HTTP — invariant API critique
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('statusHttpCodeProvider')]
    public function http_code_matches_status(ValidationStatus $status, int $expectedCode): void
    {
        $this->assertSame($expectedCode, ValidationResult::httpCodeFor($status));
    }

    public static function statusHttpCodeProvider(): array
    {
        return [
            'valid → 200'       => [ValidationStatus::Valid, 200],
            'already_used → 409' => [ValidationStatus::AlreadyUsed, 409],
            'expired → 410'     => [ValidationStatus::Expired, 410],
            'revoked → 403'     => [ValidationStatus::Revoked, 403],
            'invalid → 404'     => [ValidationStatus::Invalid, 404],
        ];
    }

    // -------------------------------------------------------------------------
    // Sérialisation Arrayable
    // -------------------------------------------------------------------------

    #[Test]
    public function to_array_filters_null_values(): void
    {
        $result = ValidationResult::invalid();

        $array = $result->toArray();

        $this->assertSame(
            ['status' => 'invalid', 'message' => 'QR Code introuvable.'],
            $array
        );
    }

    #[Test]
    public function to_array_includes_timestamps_as_atom(): void
    {
        $order = new Order(['id' => 42]);
        $qr = $this->makeQr(
            $order,
            usedAt: '2026-06-26 10:00:00',
            expiresAt: '2026-07-01 23:59:59',
        );

        $result = ValidationResult::valid($qr, partnerId: 7);
        $array = $result->toArray();

        $this->assertSame('2026-06-26T10:00:00+00:00', $array['used_at']);
        $this->assertSame('2026-07-01T23:59:59+00:00', $array['expires_at']);
        $this->assertSame(7, $array['partner_id']);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function makeQr(
        Order $order,
        ?string $usedAt = null,
        ?string $expiresAt = null,
    ): QrCode {
        $qr = new QrCode();
        $qr->setRelation('order', $order);
        $qr->order_id = $order->id;
        $qr->id = 100;
        $qr->uuid = str_repeat('a', 64);

        if ($usedAt !== null) {
            $qr->used_at = CarbonImmutable::parse($usedAt);
        }
        if ($expiresAt !== null) {
            $qr->expires_at = CarbonImmutable::parse($expiresAt);
        }

        return $qr;
    }
}