<?php

namespace Database\Factories;

use App\Enums\PartnerStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id'         => User::factory()->partner(),
            'name'            => $name,
            'slug'            => Str::slug($name) . '-' . Str::random(4),
            'status'          => PartnerStatus::Active,
            'api_calls_today' => 0,
        ];
    }

    /**
     * Partenaire actif (état par défaut).
     */
    public function active(): static
    {
        return $this->state(fn () => ['status' => PartnerStatus::Active]);
    }

    /**
     * Partenaire suspendu — doit être bloqué par EnsurePartner middleware.
     */
    public function suspended(): static
    {
        return $this->state(fn () => ['status' => PartnerStatus::Suspended]);
    }

    /**
     * Partenaire inactif.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['status' => PartnerStatus::Inactive]);
    }

    /**
     * Crée le User associé avec le bon rôle.
     * Utilisé dans les tests Feature pour authentification Sanctum.
     */
    public function withUser(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->partner()->create()->id,
        ]);
    }
}
