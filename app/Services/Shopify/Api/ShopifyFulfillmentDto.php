<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

/**
 * DTO d'un fulfillment Shopify.
 */
final readonly class ShopifyFulfillmentDto
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $trackingInfo = is_array($raw['tracking_info'] ?? null) ? $raw['tracking_info'] : [];

        return new self(
            id: isset($raw['id']) ? (int) $raw['id'] : 0,
            orderId: isset($raw['order_id']) ? (int) $raw['order_id'] : 0,
            status: (string) ($raw['status'] ?? ''),
            trackingCompany: $raw['tracking_company'] ?? null,
            trackingNumber: $raw['tracking_number'] ?? null,
            trackingUrl: $raw['tracking_url'] ?? null,
            trackingInfo: $trackingInfo,
            createdAt: $raw['created_at'] ?? null,
            updatedAt: $raw['updated_at'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $trackingInfo
     */
    public function __construct(
        public int $id,
        public int $orderId,
        public string $status,
        public ?string $trackingCompany,
        public ?string $trackingNumber,
        public ?string $trackingUrl,
        public array $trackingInfo,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'status' => $this->status,
            'tracking_company' => $this->trackingCompany,
            'tracking_number' => $this->trackingNumber,
            'tracking_url' => $this->trackingUrl,
            'tracking_info' => $this->trackingInfo,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
