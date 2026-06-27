<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

/**
 * DTO immuable représentant un client Shopify.
 *
 * ⚠️ Contient des données personnelles (RGPD) : à ne jamais logguer brut.
 */
final readonly class ShopifyCustomerDto
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $tags = [];

        if (isset($raw['tags']) && is_string($raw['tags']) && $raw['tags'] !== '') {
            $tags = array_map('trim', explode(',', $raw['tags']));
        }

        return new self(
            id: isset($raw['id']) ? (int) $raw['id'] : 0,
            email: (string) ($raw['email'] ?? ''),
            firstName: $raw['first_name'] ?? null,
            lastName: $raw['last_name'] ?? null,
            phone: $raw['phone'] ?? null,
            state: $raw['state'] ?? null,
            ordersCount: isset($raw['orders_count']) ? (int) $raw['orders_count'] : 0,
            currency: (string) ($raw['currency'] ?? 'EUR'),
            tags: $tags,
            defaultAddress: is_array($raw['default_address'] ?? null) ? $raw['default_address'] : [],
            createdAt: $raw['created_at'] ?? null,
            updatedAt: $raw['updated_at'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $defaultAddress
     */
    public function __construct(
        public int $id,
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $phone,
        public ?string $state,
        public int $ordersCount,
        public string $currency,
        public array $tags,
        public array $defaultAddress,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public function fullName(): ?string
    {
        $parts = array_filter([$this->firstName, $this->lastName]);

        return $parts === [] ? null : implode(' ', $parts);
    }

    /**
     * Représentation "safe" pour les logs (email masqué).
     */
    public function toLogArray(): array
    {
        return [
            'customer_id' => $this->id,
            'email_redacted' => $this->email !== '' ? mb_substr($this->email, 0, 2).'***' : null,
            'state' => $this->state,
            'orders_count' => $this->ordersCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'state' => $this->state,
            'orders_count' => $this->ordersCount,
            'currency' => $this->currency,
            'tags' => $this->tags,
            'default_address' => $this->defaultAddress,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
