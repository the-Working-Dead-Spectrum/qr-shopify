<?php

declare(strict_types=1);

namespace App\Contracts\Shopify;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Shopify\Api\ShopifyCustomerDto;

/**
 * Contrat du service Shopify Admin API — Customers.
 *
 * L'implémentation se trouve dans App\Services\Shopify\Api\ShopifyCustomerService.
 */
interface ShopifyCustomerApiInterface
{
    /**
     * @throws ShopifyApiException
     */
    public function find(int $customerId): ShopifyCustomerDto;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, ShopifyCustomerDto>
     *
     * @throws ShopifyApiException
     */
    public function list(array $filters = []): array;

    /**
     * @throws ShopifyApiException
     */
    public function findByEmail(string $email): ?ShopifyCustomerDto;

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ShopifyApiException
     */
    public function create(array $payload): ShopifyCustomerDto;

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ShopifyApiException
     */
    public function update(int $customerId, array $payload): ShopifyCustomerDto;

    /**
     * @throws ShopifyApiException
     */
    public function delete(int $customerId): void;
}
