<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

/**
 * DTO d'un niveau d'inventaire Shopify.
 *
 * Représente le stock disponible pour un (inventory_item, location).
 */
final readonly class ShopifyInventoryLevelDto
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            inventoryItemId: isset($raw['inventory_item_id']) ? (int) $raw['inventory_item_id'] : 0,
            locationId: isset($raw['location_id']) ? (int) $raw['location_id'] : 0,
            available: isset($raw['available']) ? (int) $raw['available'] : 0,
            updatedAt: $raw['updated_at'] ?? null,
        );
    }

    public function __construct(
        public int $inventoryItemId,
        public int $locationId,
        public int $available,
        public ?string $updatedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'inventory_item_id' => $this->inventoryItemId,
            'location_id' => $this->locationId,
            'available' => $this->available,
            'updated_at' => $this->updatedAt,
        ];
    }
}
