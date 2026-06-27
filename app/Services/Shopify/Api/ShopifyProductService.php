<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Concerns\LogsServiceActivity;
use App\Services\Shopify\ShopifyClient;

/**
 * Service Shopify Admin API — Ressource Products.
 *
 * Responsabilités :
 *  - Encapsuler les appels REST `products.json`
 *  - Normaliser les réponses en DTOs typés
 *  - Logger chaque appel (latence, status, payload)
 *
 * Aucun appel Shopify direct depuis les Controllers : on passe TOUJOURS
 * par ce service (ou les autres Api/* services).
 *
 * Documentation Shopify :
 *  https://shopify.dev/docs/api/admin-rest/2025-01/resources/product
 */
final class ShopifyProductService
{
    use LogsServiceActivity;

    public function __construct(
        private readonly ShopifyClient $client,
    ) {}

    /**
     * Récupère un produit par son ID Shopify.
     *
     * @throws ShopifyApiException
     */
    public function find(int $productId): ShopifyProductDto
    {
        $response = $this->client->get("products/{$productId}.json");

        if (! isset($response['product']) || ! is_array($response['product'])) {
            $this->warning('shopify.product.invalid_response', ['product_id' => $productId]);

            throw new ShopifyApiException(
                message: 'Invalid Shopify response: missing "product" key',
                endpoint: "products/{$productId}.json",
            );
        }

        return ShopifyProductDto::fromArray($response['product']);
    }

    /**
     * Liste les produits avec pagination et filtres.
     *
     * @param  array<string, mixed>  $filters
     *                                         - limit      : int (max 250)
     *                                         - since_id   : int (pagination cursor)
     *                                         - vendor     : string
     *                                         - product_type : string
     *                                         - status     : active|archived|draft
     *                                         - collection_id : int
     * @return array<int, ShopifyProductDto>
     *
     * @throws ShopifyApiException
     */
    public function list(array $filters = []): array
    {
        $defaults = ['limit' => 50];
        $query = array_merge($defaults, $filters);

        $response = $this->client->get('products.json', $query);

        if (! isset($response['products']) || ! is_array($response['products'])) {
            return [];
        }

        return array_map(
            static fn (array $raw): ShopifyProductDto => ShopifyProductDto::fromArray($raw),
            $response['products'],
        );
    }

    /**
     * Crée un produit Shopify.
     *
     * @param  array<string, mixed>  $payload  Payload conforme à l'API Shopify
     *
     * @throws ShopifyApiException
     */
    public function create(array $payload): ShopifyProductDto
    {
        $response = $this->client->post('products.json', ['product' => $payload]);

        if (! isset($response['product']) || ! is_array($response['product'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on product creation',
                endpoint: 'products.json',
            );
        }

        $this->info('shopify.product.created', [
            'product_id' => $response['product']['id'] ?? null,
        ]);

        return ShopifyProductDto::fromArray($response['product']);
    }

    /**
     * Met à jour un produit Shopify (merge complet).
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws ShopifyApiException
     */
    public function update(int $productId, array $payload): ShopifyProductDto
    {
        $response = $this->client->put("products/{$productId}.json", ['product' => $payload]);

        if (! isset($response['product']) || ! is_array($response['product'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on product update',
                endpoint: "products/{$productId}.json",
            );
        }

        $this->info('shopify.product.updated', ['product_id' => $productId]);

        return ShopifyProductDto::fromArray($response['product']);
    }

    /**
     * Supprime un produit Shopify.
     *
     * @throws ShopifyApiException
     */
    public function delete(int $productId): void
    {
        $this->client->delete("products/{$productId}.json");

        $this->info('shopify.product.deleted', ['product_id' => $productId]);
    }
}
