<?php

declare(strict_types=1);

namespace App\Contracts\Shopify;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Shopify\Api\ShopifyFulfillmentDto;

/**
 * Contrat du service Shopify Admin API — Fulfillments.
 */
interface ShopifyFulfillmentApiInterface
{
    /**
     * @return array<int, ShopifyFulfillmentDto>
     *
     * @throws ShopifyApiException
     */
    public function listForOrder(int $orderId): array;

    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     * @param  array<string, mixed>  $tracking
     *
     * @throws ShopifyApiException
     */
    public function create(int $orderId, array $lineItems, array $tracking = [], string $notifyCustomer = 'true'): ShopifyFulfillmentDto;

    /**
     * @param  array<string, mixed>  $tracking
     *
     * @throws ShopifyApiException
     */
    public function updateTracking(int $orderId, int $fulfillmentId, array $tracking, string $notifyCustomer = 'true'): ShopifyFulfillmentDto;

    /**
     * @throws ShopifyApiException
     */
    public function cancel(int $orderId, int $fulfillmentId): void;
}
