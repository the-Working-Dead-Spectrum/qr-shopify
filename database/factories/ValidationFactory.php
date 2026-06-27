<?php

namespace Database\Factories;

use App\Models\Partner;
use App\Models\QrCode;
use App\Models\Validation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Validation>
 */
class ValidationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'qr_code_id' => QrCode::factory()->used(),
            'partner_id' => Partner::factory(),
            'scanned_at' => now(),
            'status'     => 'valid',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => now(),
        ];
    }

    public function valid(): static
    {
        return $this->state(fn () => ['status' => 'valid']);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }

    /**
     * Validation ancienne — pour tester le job de nettoyage.
     */
    public function old(int $days = 200): static
    {
        return $this->state(fn () => [
            'scanned_at' => now()->subDays($days),
            'created_at' => now()->subDays($days),
        ]);
    }
}
