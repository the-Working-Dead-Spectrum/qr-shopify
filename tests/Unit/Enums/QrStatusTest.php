<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\QrStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QrStatusTest extends TestCase
{
    #[Test]
    public function it_has_four_states(): void
    {
        $this->assertCount(4, QrStatus::cases());
    }

    #[Test]
    public function it_maps_to_expected_string_values(): void
    {
        $this->assertSame('active', QrStatus::Active->value);
        $this->assertSame('used', QrStatus::Used->value);
        $this->assertSame('expired', QrStatus::Expired->value);
        $this->assertSame('revoked', QrStatus::Revoked->value);
    }

    #[Test]
    public function it_provides_french_labels(): void
    {
        $this->assertSame('Actif', QrStatus::Active->label());
        $this->assertSame('Utilisé', QrStatus::Used->label());
        $this->assertSame('Expiré', QrStatus::Expired->label());
        $this->assertSame('Révoqué', QrStatus::Revoked->label());
    }

    #[Test]
    public function only_active_is_not_final(): void
    {
        $this->assertFalse(QrStatus::Active->isFinal());
        $this->assertTrue(QrStatus::Used->isFinal());
        $this->assertTrue(QrStatus::Expired->isFinal());
        $this->assertTrue(QrStatus::Revoked->isFinal());
    }

    #[Test]
    public function it_can_be_constructed_from_string(): void
    {
        $this->assertSame(QrStatus::Active, QrStatus::from('active'));
        $this->assertSame(QrStatus::Revoked, QrStatus::from('revoked'));
    }
}