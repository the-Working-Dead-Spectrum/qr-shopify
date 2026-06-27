<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ValidationStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValidationStatusTest extends TestCase
{
    #[Test]
    public function it_has_five_states(): void
    {
        $this->assertCount(5, ValidationStatus::cases());
    }

    #[Test]
    public function it_maps_to_expected_string_values(): void
    {
        $this->assertSame('valid', ValidationStatus::Valid->value);
        $this->assertSame('already_used', ValidationStatus::AlreadyUsed->value);
        $this->assertSame('expired', ValidationStatus::Expired->value);
        $this->assertSame('revoked', ValidationStatus::Revoked->value);
        $this->assertSame('invalid', ValidationStatus::Invalid->value);
    }

    #[Test]
    public function it_provides_french_labels(): void
    {
        $this->assertSame('Valide', ValidationStatus::Valid->label());
        $this->assertSame('Déjà utilisé', ValidationStatus::AlreadyUsed->label());
        $this->assertSame('Expiré', ValidationStatus::Expired->label());
        $this->assertSame('Révoqué', ValidationStatus::Revoked->label());
        $this->assertSame('Invalide', ValidationStatus::Invalid->label());
    }

    #[Test]
    public function it_can_be_used_as_array_keys(): void
    {
        $values = array_map(fn (ValidationStatus $s) => $s->value, ValidationStatus::cases());
        $this->assertContains('valid', $values);
        $this->assertContains('invalid', $values);
    }
}