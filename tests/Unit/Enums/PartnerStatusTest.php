<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PartnerStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PartnerStatusTest extends TestCase
{
    #[Test]
    public function it_has_three_states(): void
    {
        $this->assertCount(3, PartnerStatus::cases());
    }

    #[Test]
    public function it_maps_to_expected_string_values(): void
    {
        $this->assertSame('active', PartnerStatus::Active->value);
        $this->assertSame('inactive', PartnerStatus::Inactive->value);
        $this->assertSame('suspended', PartnerStatus::Suspended->value);
    }

    #[Test]
    public function it_provides_french_labels(): void
    {
        $this->assertSame('Actif', PartnerStatus::Active->label());
        $this->assertSame('Inactif', PartnerStatus::Inactive->label());
        $this->assertSame('Suspendu', PartnerStatus::Suspended->label());
    }

    #[Test]
    public function only_active_is_operational(): void
    {
        $this->assertTrue(PartnerStatus::Active->isOperational());
        $this->assertFalse(PartnerStatus::Inactive->isOperational());
        $this->assertFalse(PartnerStatus::Suspended->isOperational());
    }
}