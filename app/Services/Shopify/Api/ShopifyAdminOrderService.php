<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Concerns\LogsServiceActivity;
use App\Services\Shopify\ShopifyClient;

/**
 * Service Shopify Admin API — Ressource Orders (lecture + actions).
 *
 * ⚠️ À ne pas confondre avec App\Services\ShopifyService qui gère
 * les webhooks ENTRANTS. Ici on parle de l'API SORTANTE pour
 * consulter / modifier des commandes depuis Laravel.
 *
 * Documentation :
 *  https://shopify.dev/docs/api/admin-rest/2025-01/resources/order
 */
final class ShopifyAdminOrderService
{
    use LogsServiceActivity;

    public function __construct(
        private readonly ShopifyClient $client,
    ) {}

    /**
     * Récupère une commande Shopify par ID.
     *
     * @throws ShopifyApiException
     */
    public function find(int $orderId): ShopifyAdminOrderDto
    {
        $response = $this->client->get("orders/{$orderId}.json");

        if (! isset($response['order']) || ! is_array($response['order'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response: missing "order" key',
                endpoint: "orders/{$orderId}.json",
            );
        }

        return ShopifyAdminOrderDto::fromArray($response['order']);
    }

    /**
     * Liste les commandes avec filtres.
     *
     * @param  array<string, mixed>  $filters
     *                                         - limit, since_id, status, financial_status, fulfillment_status
     *                                         - processed_at_min, processed_at_max
     *                                         - customer_id
     * @return array<int, ShopifyAdminOrderDto>
     *
     * @throws ShopifyApiException
     */
    public function list(array $filters = []): array
    {
        $defaults = ['limit' => 50];
        $query = array_merge($defaults, $filters);

        $response = $this->client->get('orders.json', $query);

        if (! isset($response['orders']) || ! is_array($response['orders'])) {
            return [];
        }

        return array_map(
            static fn (array $raw): ShopifyAdminOrderDto => ShopifyAdminOrderDto::fromArray($raw),
            $response['orders'],
        );
    }

    /**
     * Annule une commande Shopify (équivalent orders/cancelled).
     *
     * @param  string|null  $reason  Raison d'annulation (customer|declined|fraud|other)
     *
     * @throws ShopifyApiException
     */
    public function cancel(int $orderId, ?string $reason = null, bool $restock = true): ShopifyAdminOrderDto
    {
        $response = $this->client->post("orders/{$orderId}/cancel.json", [
            'reason' => $reason,
            'restock' => $restock,
            'notify' => true,
        ]);

        if (! isset($response['order']) || ! is_array($response['order'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on order cancel',
                endpoint: "orders/{$orderId}/cancel.json",
            );
        }

        $this->warning('shopify.order.cancelled_via_api', ['order_id' => $orderId]);

        return ShopifyAdminOrderDto::fromArray($response['order']);
    }

    /**
     * Met à jour les métadonnées d'une commande (note, tags).
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws ShopifyApiException
     */
    public function update(int $orderId, array $payload): ShopifyAdminOrderDto
    {
        $response = $this->client->put("orders/{$orderId}.json", ['order' => $payload]);

        if (! isset($response['order']) || ! is_array($response['order'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on order update',
                endpoint: "orders/{$orderId}.json",
            );
        }

        $this->info('shopify.order.updated_via_api', ['order_id' => $orderId]);

        return ShopifyAdminOrderDto::fromArray($response['order']);
    }
}
