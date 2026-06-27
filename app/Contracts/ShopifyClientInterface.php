<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\Shopify\ShopifyApiException;

/**
 * Contrat du client HTTP sortant vers Shopify Admin API.
 *
 * Toutes les méthodes retournent un tableau associatif décodé du JSON
 * de réponse, ou throws une ShopifyApiException en cas d'échec.
 */
interface ShopifyClientInterface
{
    /**
     * Requête GET.
     *
     * @param  string  $endpoint  Endpoint relatif (ex: "orders/123.json")
     * @param  array<string, mixed>  $query  Query string
     * @return array<string, mixed>
     *
     * @throws ShopifyApiException
     */
    public function get(string $endpoint, array $query = []): array;

    /**
     * Requête POST.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ShopifyApiException
     */
    public function post(string $endpoint, array $data = []): array;

    /**
     * Requête PUT.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ShopifyApiException
     */
    public function put(string $endpoint, array $data = []): array;

    /**
     * Requête DELETE.
     *
     * @return array<string, mixed>
     *
     * @throws ShopifyApiException
     */
    public function delete(string $endpoint): array;
}
