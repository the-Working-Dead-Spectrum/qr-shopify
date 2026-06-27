<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ShopifyWebhookEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour ShopifyWebhookEvent.
 *
 * Permet de générer des événements webhook pour les tests :
 *  - sans ID (sera généré par Shopify)
 *  - avec différents statuts
 *  - avec différents topics
 */
class ShopifyWebhookEventFactory extends Factory
{
    protected $model = ShopifyWebhookEvent::class;

    public function definition(): array
    {
        return [
            'webhook_id'   => 'wh_' . $this->faker->unique()->bothify('??????????##########'),
            'topic'        => 'orders/paid',
            'shop_domain'  => $this->faker->domainName(),
            'payload_hash' => hash('sha256', $this->faker->text()),
            'status'       => 'received',
            'shopify_order_id' => (string) $this->faker->numberBetween(100000000, 999999999),
            'meta'         => ['source' => 'factory'],
            'received_at'  => CarbonImmutable::now(),
            'processed_at' => null,
        ];
    }

    public function processed(): self
    {
        return $this->state(fn () => [
            'status' => 'processed',
            'processed_at' => CarbonImmutable::now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'processed_at' => CarbonImmutable::now(),
            'meta' => ['failure_reason' => 'Test failure'],
        ]);
    }

    public function forTopic(string $topic): self
    {
        return $this->state(fn () => ['topic' => $topic]);
    }
}