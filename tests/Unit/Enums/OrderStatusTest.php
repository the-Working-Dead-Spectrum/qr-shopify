<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    #[Test]
    public function it_has_expected_states(): void
    {
        $values = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::cases());
        $this->assertContains('paid', $values);
        $this->assertContains('pending', $values);
        $this->assertContains('cancelled', $values);
    }
}