<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

/**
 * DTO d'une variante de produit Shopify.
 */
final readonly class ShopifyVariantDto
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $priceCents = isset($raw['price'])
            ? (int) round(((float) $raw['price']) * 100)
            : 0;

        return new self(
            id: isset($raw['id']) ? (int) $raw['id'] : 0,
            productId: isset($raw['product_id']) ? (int) $raw['product_id'] : 0,
            title: (string) ($raw['title'] ?? ''),
            sku: (string) ($raw['sku'] ?? ''),
            priceCents: $priceCents,
            currency: (string) ($raw['currency'] ?? 'EUR'),
            inventoryQuantity: isset($raw['inventory_quantity']) ? (int) $raw['inventory_quantity'] : 0,
            inventoryItemId: isset($raw['inventory_item_id']) ? (int) $raw['inventory_item_id'] : null,
            weightGrams: isset($raw['weight']) ? (int) $raw['weight'] : null,
            taxable: (bool) ($raw['taxable'] ?? true),
            requiresShipping: (bool) ($raw['requires_shipping'] ?? true),
        );
    }

    public function __construct(
        public int $id,
        public int $productId,
        public string $title,
        public string $sku,
        public int $priceCents,
        public string $currency,
        public int $inventoryQuantity,
        public ?int $inventoryItemId,
        public ?int $weightGrams,
        public bool $taxable,
        public bool $requiresShipping,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'title' => $this->title,
            'sku' => $this->sku,
            'price_cents' => $this->priceCents,
            'currency' => $this->currency,
            'inventory_quantity' => $this->inventoryQuantity,
            'inventory_item_id' => $this->inventoryItemId,
            'weight_grams' => $this->weightGrams,
            'taxable' => $this->taxable,
            'requires_shipping' => $this->requiresShipping,
        ];
    }
}
