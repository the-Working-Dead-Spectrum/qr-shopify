<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Concerns\LogsServiceActivity;
use App\Services\Shopify\ShopifyClient;

/**
 * Service Shopify Admin API — Ressource Customers.
 *
 * Note RGPD : ce service manipule des données personnelles. Les logs
 * NE DOIVENT JAMAIS contenir l'email complet ni le téléphone — c'est
 * déjà garanti par LogsServiceActivity::sanitize() + le masquage
 * automatique du payload via ShopifyClient.
 *
 * Documentation :
 *  https://shopify.dev/docs/api/admin-rest/2025-01/resources/customer
 */
final class ShopifyCustomerService
{
    use LogsServiceActivity;

    public function __construct(
        private readonly ShopifyClient $client,
    ) {}

    /**
     * Récupère un client par son ID Shopify.
     *
     * @throws ShopifyApiException
     */
    public function find(int $customerId): ShopifyCustomerDto
    {
        $response = $this->client->get("customers/{$customerId}.json");

        if (! isset($response['customer']) || ! is_array($response['customer'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response: missing "customer" key',
                endpoint: "customers/{$customerId}.json",
            );
        }

        return ShopifyCustomerDto::fromArray($response['customer']);
    }

    /**
     * Liste les clients avec pagination.
     *
     * @param  array<string, mixed>  $filters
     *                                         - limit  : int
     *                                         - since_id : int
     *                                         - ids   : string (IDs séparés par virgule)
     *                                         - query : string (recherche texte)
     * @return array<int, ShopifyCustomerDto>
     *
     * @throws ShopifyApiException
     */
    public function list(array $filters = []): array
    {
        $defaults = ['limit' => 50];
        $query = array_merge($defaults, $filters);

        $response = $this->client->get('customers.json', $query);

        if (! isset($response['customers']) || ! is_array($response['customers'])) {
            return [];
        }

        return array_map(
            static fn (array $raw): ShopifyCustomerDto => ShopifyCustomerDto::fromArray($raw),
            $response['customers'],
        );
    }

    /**
     * Recherche un client par email.
     *
     * @return ShopifyCustomerDto|null null si non trouvé
     *
     * @throws ShopifyApiException
     */
    public function findByEmail(string $email): ?ShopifyCustomerDto
    {
        $response = $this->client->get('customers.json', [
            'email' => $email,
            'limit' => 1,
        ]);

        if (! isset($response['customers'][0]) || ! is_array($response['customers'][0])) {
            return null;
        }

        return ShopifyCustomerDto::fromArray($response['customers'][0]);
    }

    /**
     * Crée un client Shopify.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws ShopifyApiException
     */
    public function create(array $payload): ShopifyCustomerDto
    {
        $response = $this->client->post('customers.json', ['customer' => $payload]);

        if (! isset($response['customer']) || ! is_array($response['customer'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on customer creation',
                endpoint: 'customers.json',
            );
        }

        $this->info('shopify.customer.created', [
            'customer_id' => $response['customer']['id'] ?? null,
        ]);

        return ShopifyCustomerDto::fromArray($response['customer']);
    }

    /**
     * Met à jour un client Shopify.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws ShopifyApiException
     */
    public function update(int $customerId, array $payload): ShopifyCustomerDto
    {
        $response = $this->client->put("customers/{$customerId}.json", ['customer' => $payload]);

        if (! isset($response['customer']) || ! is_array($response['customer'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on customer update',
                endpoint: "customers/{$customerId}.json",
            );
        }

        $this->info('shopify.customer.updated', ['customer_id' => $customerId]);

        return ShopifyCustomerDto::fromArray($response['customer']);
    }

    /**
     * Supprime un client Shopify (GDPR — suppression totale).
     *
     * ⚠️ Action irréversible côté Shopify.
     *
     * @throws ShopifyApiException
     */
    public function delete(int $customerId): void
    {
        $this->client->delete("customers/{$customerId}.json");

        $this->warning('shopify.customer.deleted', ['customer_id' => $customerId]);
    }
}
