<?php

declare(strict_types=1);

namespace App\Contracts\Shopify;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Shopify\Api\ShopifyProductDto;

/**
 * Contrat du service Shopify Admin API — Products.
 */
interface ShopifyProductApiInterface
{
    /**
     * @throws ShopifyApiException
     */
    public function find(int $productId): ShopifyProductDto;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, ShopifyProductDto>
     *
     * @throws ShopifyApiException
     */
    public function list(array $filters = []): array;

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ShopifyApiException
     */
    public function create(array $payload): ShopifyProductDto;

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ShopifyApiException
     */
    public function update(int $productId, array $payload): ShopifyProductDto;

    /**
     * @throws ShopifyApiException
     */
    public function delete(int $productId): void;
}
