<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Enums\OrderStatus;
use App\Exceptions\Service\InvalidPayloadException;
use App\Exceptions\Shopify\InvalidWebhookException;

/**
 * DTO d'un payload webhook Shopify `orders/*` ou `refunds/create`.
 *
 * Découple le service du format Shopify brut. Valide les champs requis
 * une fois, en amont, et expose des accesseurs typés.
 *
 * Extension de la version initiale pour couvrir tous les champs utiles :
 *  - line_items (pour produits achetés, montant précis)
 *  - financial_status / fulfillment_status (statuts Shopify natifs)
 *  - cancelled_at / closed_at
 *  - refunds (informations de remboursement)
 *  - customer.id (pour traçabilité)
 *  - source_name / landing_site (analytics)
 *
 * Pourquoi pas un array associatif : un DTO typé documente le contrat,
 * détecte les erreurs à la frontière (invalid payload), et autorise le
 * refactoring côté Shopify sans toucher au métier.
 */
final readonly class OrderPayload
{
    /**
     * Construit le DTO depuis le payload Shopify brut.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws InvalidPayloadException Payload legacy (rétro-compat).
     * @throws InvalidWebhookException Payload nouveau format.
     */
    public static function fromShopify(array $payload): self
    {
        // Shopify envoie 'id' (entier ou string) comme identifiant de commande.
        $shopifyOrderId = $payload['id'] ?? $payload['admin_graphql_api_id'] ?? null;

        if (! is_scalar($shopifyOrderId)) {
            throw new InvalidWebhookException(
                'Champ "id" manquant dans le payload Shopify.',
                ['payload_keys' => array_keys($payload)],
            );
        }

        // Extraction email + nom depuis customer ou email direct.
        $email = $payload['email']
            ?? $payload['customer']['email']
            ?? $payload['contact_email']
            ?? null;

        if (! is_string($email) || $email === '') {
            throw new InvalidWebhookException(
                'Email client manquant dans le payload Shopify.',
                ['has_customer' => isset($payload['customer'])],
            );
        }

        $name = $payload['customer']['first_name'] ?? null;
        $lastname = $payload['customer']['last_name'] ?? null;
        $fullName = trim(($name ?? '').' '.($lastname ?? ''));

        // Le total_price Shopify est en décimal (string). Conversion cents.
        $amountCents = isset($payload['total_price'])
            ? (int) round(((float) $payload['total_price']) * 100)
            : 0;

        $currency = (string) ($payload['currency'] ?? 'EUR');

        // cancelled_at n'est présent que sur les webhooks d'annulation.
        $cancelledAt = $payload['cancelled_at'] ?? null;
        $closedAt = $payload['closed_at'] ?? null;
        $processedAt = $payload['processed_at'] ?? null;

        $lineItems = self::extractLineItems($payload);
        $customer = self::extractCustomer($payload);

        return new self(
            shopifyOrderId: (string) $shopifyOrderId,
            customerEmail: $email,
            customerName: $fullName !== '' ? $fullName : null,
            amountCents: $amountCents,
            currency: $currency,
            cancelledAt: is_string($cancelledAt) ? $cancelledAt : null,
            financialStatus: is_string($payload['financial_status'] ?? null) ? $payload['financial_status'] : null,
            fulfillmentStatus: is_string($payload['fulfillment_status'] ?? null) ? $payload['fulfillment_status'] : null,
            closedAt: is_string($closedAt) ? $closedAt : null,
            lineItems: $lineItems,
            customer: $customer,
            note: is_string($payload['note'] ?? null) ? $payload['note'] : null,
            sourceName: is_string($payload['source_name'] ?? null) ? $payload['source_name'] : null,
            processedAt: is_string($processedAt) ? $processedAt : null,
        );
    }

    public function __construct(
        public string $shopifyOrderId,
        public string $customerEmail,
        public ?string $customerName,
        public int $amountCents,
        public string $currency,
        public ?string $cancelledAt = null,
        public ?string $financialStatus = null,
        public ?string $fulfillmentStatus = null,
        public ?string $closedAt = null,
        /** @var array<int, array<string, mixed>> */
        public array $lineItems = [],
        /** @var array<string, mixed> */
        public array $customer = [],
        public ?string $note = null,
        public ?string $sourceName = null,
        public ?string $processedAt = null,
    ) {}

    public function isCancelled(): bool
    {
        return $this->cancelledAt !== null
            || $this->financialStatus === 'refunded'
            || $this->financialStatus === 'voided';
    }

    public function isPaid(): bool
    {
        return $this->financialStatus === 'paid'
            || $this->financialStatus === 'partially_paid';
    }

    /**
     * Mappe le financial_status Shopify vers notre enum interne.
     */
    public function toOrderStatus(): OrderStatus
    {
        return match (true) {
            $this->isCancelled() => OrderStatus::Cancelled,
            $this->isPaid() => OrderStatus::Paid,
            default => OrderStatus::Pending,
        };
    }

    /**
     * Extrait et normalise les line items Shopify.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private static function extractLineItems(array $payload): array
    {
        if (! isset($payload['line_items']) || ! is_array($payload['line_items'])) {
            return [];
        }

        $items = [];

        foreach ($payload['line_items'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[] = [
                'shopify_product_id' => isset($item['product_id']) ? (string) $item['product_id'] : null,
                'shopify_variant_id' => isset($item['variant_id']) ? (string) $item['variant_id'] : null,
                'title' => (string) ($item['title'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'price_cents' => isset($item['price']) ? (int) round(((float) $item['price']) * 100) : 0,
                'sku' => $item['sku'] ?? null,
            ];
        }

        return $items;
    }

    /**
     * Extrait les informations client du payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function extractCustomer(array $payload): array
    {
        if (! isset($payload['customer']) || ! is_array($payload['customer'])) {
            return [];
        }

        $c = $payload['customer'];

        return [
            'id' => isset($c['id']) ? (string) $c['id'] : null,
            'email' => $c['email'] ?? null,
            'first_name' => $c['first_name'] ?? null,
            'last_name' => $c['last_name'] ?? null,
            'phone' => $c['phone'] ?? null,
            'orders_count' => isset($c['orders_count']) ? (int) $c['orders_count'] : null,
        ];
    }
}
