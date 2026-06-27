<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Concerns\LogsServiceActivity;
use App\Services\Shopify\ShopifyClient;

/**
 * Service Shopify Admin API — Ressource Fulfillments.
 *
 * Gère la création et le suivi des expéditions de commandes Shopify.
 * Important pour notre cas d'usage : quand un QR Code est validé par
 * un partenaire, on peut notifier Shopify que la "prestation QR" est
 * fulfilled (utile pour les analytics e-commerce).
 *
 * Documentation :
 *  https://shopify.dev/docs/api/admin-rest/2025-01/resources/fulfillment
 */
final class ShopifyFulfillmentService
{
    use LogsServiceActivity;

    public function __construct(
        private readonly ShopifyClient $client,
    ) {}

    /**
     * Liste les fulfillments d'une commande.
     *
     * @return array<int, ShopifyFulfillmentDto>
     *
     * @throws ShopifyApiException
     */
    public function listForOrder(int $orderId): array
    {
        $response = $this->client->get("orders/{$orderId}/fulfillments.json");

        if (! isset($response['fulfillments']) || ! is_array($response['fulfillments'])) {
            return [];
        }

        return array_map(
            static fn (array $raw): ShopifyFulfillmentDto => ShopifyFulfillmentDto::fromArray($raw),
            $response['fulfillments'],
        );
    }

    /**
     * Crée un fulfillment pour une commande.
     *
     * @param  array<int, array<string, mixed>>  $lineItems
     *                                                       line_items : [{id: int, quantity: int}, ...]
     * @param  array<string, mixed>  $tracking
     *                                          tracking_number, tracking_company, tracking_url
     *
     * @throws ShopifyApiException
     */
    public function create(int $orderId, array $lineItems, array $tracking = [], string $notifyCustomer = 'true'): ShopifyFulfillmentDto
    {
        $payload = [
            'fulfillment' => [
                'line_items' => $lineItems,
                'notify_customer' => $notifyCustomer,
                'tracking_info' => $tracking,
            ],
        ];

        $response = $this->client->post("orders/{$orderId}/fulfillments.json", $payload);

        if (! isset($response['fulfillment']) || ! is_array($response['fulfillment'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on fulfillment creation',
                endpoint: "orders/{$orderId}/fulfillments.json",
            );
        }

        $this->info('shopify.fulfillment.created', [
            'order_id' => $orderId,
            'fulfillment_id' => $response['fulfillment']['id'] ?? null,
        ]);

        return ShopifyFulfillmentDto::fromArray($response['fulfillment']);
    }

    /**
     * Met à jour le tracking d'un fulfillment.
     *
     * @param  array<string, mixed>  $tracking
     *
     * @throws ShopifyApiException
     */
    public function updateTracking(int $orderId, int $fulfillmentId, array $tracking, string $notifyCustomer = 'true'): ShopifyFulfillmentDto
    {
        $payload = [
            'fulfillment' => [
                'id' => $fulfillmentId,
                'tracking_info' => $tracking,
                'notify_customer' => $notifyCustomer,
            ],
        ];

        $response = $this->client->put("orders/{$orderId}/fulfillments/{$fulfillmentId}.json", $payload);

        if (! isset($response['fulfillment']) || ! is_array($response['fulfillment'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on fulfillment tracking update',
                endpoint: "orders/{$orderId}/fulfillments/{$fulfillmentId}.json",
            );
        }

        $this->info('shopify.fulfillment.tracking_updated', [
            'order_id' => $orderId,
            'fulfillment_id' => $fulfillmentId,
        ]);

        return ShopifyFulfillmentDto::fromArray($response['fulfillment']);
    }

    /**
     * Annule un fulfillment (met le stock de retour en disponible).
     *
     * @throws ShopifyApiException
     */
    public function cancel(int $orderId, int $fulfillmentId): void
    {
        $this->client->post("orders/{$orderId}/fulfillments/{$fulfillmentId}/cancel.json");

        $this->warning('shopify.fulfillment.cancelled', [
            'order_id' => $orderId,
            'fulfillment_id' => $fulfillmentId,
        ]);
    }
}
