<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

/**
 * DTO immuable représentant un produit Shopify.
 *
 * Découple le code métier du format brut de l'API Shopify.
 * Toutes les valeurs optionnelles sont typées pour faciliter la lecture.
 */
final readonly class ShopifyProductDto
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $variants = [];

        foreach (($raw['variants'] ?? []) as $v) {
            if (is_array($v)) {
                $variants[] = ShopifyVariantDto::fromArray($v);
            }
        }

        $images = [];

        foreach (($raw['images'] ?? []) as $img) {
            if (is_array($img)) {
                $images[] = [
                    'id' => isset($img['id']) ? (int) $img['id'] : null,
                    'src' => $img['src'] ?? null,
                    'alt' => $img['alt'] ?? null,
                ];
            }
        }

        $tags = [];

        if (isset($raw['tags']) && is_string($raw['tags']) && $raw['tags'] !== '') {
            $tags = array_map('trim', explode(',', $raw['tags']));
        }

        return new self(
            id: isset($raw['id']) ? (int) $raw['id'] : 0,
            title: (string) ($raw['title'] ?? ''),
            handle: $raw['handle'] ?? null,
            bodyHtml: $raw['body_html'] ?? null,
            vendor: (string) ($raw['vendor'] ?? ''),
            productType: (string) ($raw['product_type'] ?? ''),
            status: (string) ($raw['status'] ?? 'active'),
            tags: $tags,
            variants: $variants,
            images: $images,
            createdAt: $raw['created_at'] ?? null,
            updatedAt: $raw['updated_at'] ?? null,
        );
    }

    /**
     * @param  array<int, ShopifyVariantDto>  $variants
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $handle,
        public ?string $bodyHtml,
        public string $vendor,
        public string $productType,
        public string $status,
        public array $tags,
        public array $variants,
        public array $images,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'body_html' => $this->bodyHtml,
            'vendor' => $this->vendor,
            'product_type' => $this->productType,
            'status' => $this->status,
            'tags' => $this->tags,
            'variants' => array_map(fn (ShopifyVariantDto $v) => $v->toArray(), $this->variants),
            'images' => $this->images,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
