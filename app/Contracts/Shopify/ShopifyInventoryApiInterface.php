<?php

declare(strict_types=1);

namespace App\Contracts\Shopify;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Shopify\Api\ShopifyInventoryLevelDto;

/**
 * Contrat du service Shopify Admin API — Inventory Levels.
 */
interface ShopifyInventoryApiInterface
{
    /**
     * @return array<int, ShopifyInventoryLevelDto>
     *
     * @throws ShopifyApiException
     */
    public function levels(int $inventoryItemId): array;

    /**
     * @param  array<int, int>  $inventoryItemIds
     * @return array<int, ShopifyInventoryLevelDto>
     *
     * @throws ShopifyApiException
     */
    public function listByItems(array $inventoryItemIds, ?int $locationId = null): array;

    /**
     * @throws ShopifyApiException
     */
    public function adjust(int $inventoryItemId, int $locationId, int $delta, bool $available = true): ShopifyInventoryLevelDto;

    /**
     * @throws ShopifyApiException
     */
    public function connect(int $inventoryItemId, int $locationId, bool $relocateIfNecessary = false): ShopifyInventoryLevelDto;

    /**
     * @throws ShopifyApiException
     */
    public function set(int $inventoryItemId, int $locationId, int $available): ShopifyInventoryLevelDto;
}
