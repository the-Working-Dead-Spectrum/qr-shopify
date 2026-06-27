<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

/**
 * DTO d'une commande Shopify telle que retournée par l'Admin API.
 */
final readonly class ShopifyAdminOrderDto
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $totalCents = isset($raw['total_price'])
            ? (int) round(((float) $raw['total_price']) * 100)
            : 0;

        $subtotalCents = isset($raw['subtotal_price'])
            ? (int) round(((float) $raw['subtotal_price']) * 100)
            : 0;

        $totalTaxCents = isset($raw['total_tax'])
            ? (int) round(((float) $raw['total_tax']) * 100)
            : 0;

        $lineItems = [];

        foreach (($raw['line_items'] ?? []) as $li) {
            if (! is_array($li)) {
                continue;
            }

            $priceCents = isset($li['price'])
                ? (int) round(((float) $li['price']) * 100)
                : 0;

            $lineItems[] = [
                'id' => isset($li['id']) ? (int) $li['id'] : null,
                'product_id' => isset($li['product_id']) ? (int) $li['product_id'] : null,
                'variant_id' => isset($li['variant_id']) ? (int) $li['variant_id'] : null,
                'title' => (string) ($li['title'] ?? ''),
                'quantity' => (int) ($li['quantity'] ?? 0),
                'price_cents' => $priceCents,
                'sku' => $li['sku'] ?? null,
                'fulfillment_status' => $li['fulfillment_status'] ?? null,
            ];
        }

        $tags = [];

        if (isset($raw['tags']) && is_string($raw['tags']) && $raw['tags'] !== '') {
            $tags = array_map('trim', explode(',', $raw['tags']));
        }

        $customerId = null;

        if (isset($raw['customer']) && is_array($raw['customer']) && isset($raw['customer']['id'])) {
            $customerId = (int) $raw['customer']['id'];
        }

        return new self(
            id: isset($raw['id']) ? (int) $raw['id'] : 0,
            name: (string) ($raw['name'] ?? ''),
            email: (string) ($raw['email'] ?? ''),
            financialStatus: (string) ($raw['financial_status'] ?? ''),
            fulfillmentStatus: $raw['fulfillment_status'] ?? null,
            totalCents: $totalCents,
            subtotalCents: $subtotalCents,
            totalTaxCents: $totalTaxCents,
            currency: (string) ($raw['currency'] ?? 'EUR'),
            cancelledAt: $raw['cancelled_at'] ?? null,
            processedAt: $raw['processed_at'] ?? null,
            lineItems: $lineItems,
            customerId: $customerId,
            tags: $tags,
        );
    }

    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $financialStatus,
        public ?string $fulfillmentStatus,
        public int $totalCents,
        public int $subtotalCents,
        public int $totalTaxCents,
        public string $currency,
        public ?string $cancelledAt,
        public ?string $processedAt,
        public array $lineItems,
        public ?int $customerId,
        public array $tags,
    ) {}

    public function isPaid(): bool
    {
        return in_array($this->financialStatus, ['paid', 'partially_paid'], true);
    }

    public function isCancelled(): bool
    {
        return $this->cancelledAt !== null
            || in_array($this->financialStatus, ['refunded', 'voided'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'financial_status' => $this->financialStatus,
            'fulfillment_status' => $this->fulfillmentStatus,
            'total_cents' => $this->totalCents,
            'subtotal_cents' => $this->subtotalCents,
            'total_tax_cents' => $this->totalTaxCents,
            'currency' => $this->currency,
            'cancelled_at' => $this->cancelledAt,
            'processed_at' => $this->processedAt,
            'line_items' => $this->lineItems,
            'customer_id' => $this->customerId,
            'tags' => $this->tags,
        ];
    }
}
