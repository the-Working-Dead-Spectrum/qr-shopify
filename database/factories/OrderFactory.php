<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shopify_order_id' => (string) fake()->unique()->numerify('######'),
            'customer_email'   => fake()->safeEmail(),
            'customer_name'    => fake()->name(),
            'amount_cents'     => fake()->numberBetween(1000, 50000),
            'currency'         => 'EUR',
            'status'           => OrderStatus::Paid,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Paid]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Pending]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Cancelled]);
    }
}
