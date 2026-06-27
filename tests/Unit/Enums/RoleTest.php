<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Role;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    #[Test]
    public function it_has_admin_and_partner(): void
    {
        $values = array_map(fn (Role $r) => $r->value, Role::cases());

        $this->assertContains('admin', $values);
        $this->assertContains('partner', $values);
    }
}