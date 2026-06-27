<?php

namespace Database\Factories;

use App\Enums\QrStatus;
use App\Models\Order;
use App\Models\QrCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QrCode>
 */
class QrCodeFactory extends Factory
{
    public function definition(): array
    {
        // Simule un hash HMAC-SHA256 (64 chars hex)
        return [
            'uuid'             => hash('sha256', Str::uuid()->toString()),
            'order_id'         => Order::factory(),
            'status'           => QrStatus::Active,
            'used_at'          => null,
            'expires_at'       => now()->addDays(7),
            'partner_id'       => null,
            'regenerated_from' => null,
        ];
    }

    /**
     * QR actif et non expiré — le cas standard avant scan.
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'status'     => QrStatus::Active,
            'used_at'    => null,
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * QR déjà utilisé — doit retourner 409.
     */
    public function used(): static
    {
        return $this->state(fn () => [
            'status'  => QrStatus::Used,
            'used_at' => now()->subHour(),
        ]);
    }

    /**
     * QR expiré — expires_at dans le passé, status encore 'active'
     * (le job d'expiration n'a pas encore tourné).
     * Doit retourner 410.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status'     => QrStatus::Active,
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * QR révoqué manuellement — doit retourner 403.
     */
    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => QrStatus::Revoked,
        ]);
    }

    /**
     * QR sans expiration (permanent).
     */
    public function permanent(): static
    {
        return $this->state(fn () => [
            'expires_at' => null,
        ]);
    }
}
