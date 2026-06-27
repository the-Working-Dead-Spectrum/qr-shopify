<?php

declare(strict_types=1);

namespace App\Contracts\Shopify;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Shopify\Api\ShopifyAdminOrderDto;

/**
 * Contrat du service Shopify Admin API — Orders (sortant).
 *
 * ⚠️ Ne pas confondre avec le traitement des webhooks ENTRANTS
 * (App\Services\ShopifyService) — ici on lit/modifie les commandes
 * via l'Admin API REST.
 */
interface ShopifyAdminOrderApiInterface
{
    /**
     * @throws ShopifyApiException
     */
    public function find(int $orderId): ShopifyAdminOrderDto;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, ShopifyAdminOrderDto>
     *
     * @throws ShopifyApiException
     */
    public function list(array $filters = []): array;

    /**
     * @throws ShopifyApiException
     */
    public function cancel(int $orderId, ?string $reason = null, bool $restock = true): ShopifyAdminOrderDto;

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ShopifyApiException
     */
    public function update(int $orderId, array $payload): ShopifyAdminOrderDto;
}
