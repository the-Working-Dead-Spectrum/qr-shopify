<?php

declare(strict_types=1);

namespace App\Services\Shopify\Api;

use App\Exceptions\Shopify\ShopifyApiException;
use App\Services\Concerns\LogsServiceActivity;
use App\Services\Shopify\ShopifyClient;

/**
 * Service Shopify Admin API — Ressource Inventory Levels.
 *
 * L'inventaire Shopify fonctionne par niveaux (inventory_levels) rattachés
 * à des inventory_item_id. La logique d'ajustement (delta) doit être
 * précautionneuse : un mauvais appel peut créer un stock négatif.
 *
 * Documentation :
 *  https://shopify.dev/docs/api/admin-rest/2025-01/resources/inventorylevel
 */
final class ShopifyInventoryService
{
    use LogsServiceActivity;

    public function __construct(
        private readonly ShopifyClient $client,
    ) {}

    /**
     * Récupère les niveaux de stock pour un inventory_item_id.
     *
     * @return array<int, ShopifyInventoryLevelDto>
     *
     * @throws ShopifyApiException
     */
    public function levels(int $inventoryItemId): array
    {
        $response = $this->client->get('inventory_levels.json', [
            'inventory_item_ids' => (string) $inventoryItemId,
        ]);

        if (! isset($response['inventory_levels']) || ! is_array($response['inventory_levels'])) {
            return [];
        }

        return array_map(
            static fn (array $raw): ShopifyInventoryLevelDto => ShopifyInventoryLevelDto::fromArray($raw),
            $response['inventory_levels'],
        );
    }

    /**
     * Liste les niveaux de stock pour plusieurs inventory_item_ids en une fois.
     *
     * @param  array<int, int>  $inventoryItemIds
     * @return array<int, ShopifyInventoryLevelDto>
     *
     * @throws ShopifyApiException
     */
    public function listByItems(array $inventoryItemIds, ?int $locationId = null): array
    {
        if ($inventoryItemIds === []) {
            return [];
        }

        $query = ['inventory_item_ids' => implode(',', $inventoryItemIds)];

        if ($locationId !== null) {
            $query['location_id'] = $locationId;
        }

        $response = $this->client->get('inventory_levels.json', $query);

        if (! isset($response['inventory_levels']) || ! is_array($response['inventory_levels'])) {
            return [];
        }

        return array_map(
            static fn (array $raw): ShopifyInventoryLevelDto => ShopifyInventoryLevelDto::fromArray($raw),
            $response['inventory_levels'],
        );
    }

    /**
     * Ajuste le stock d'un inventory_item dans une location donnée (delta).
     *
     * @param  int  $delta  Quantité à ajouter (positif) ou retirer (négatif).
     *
     * @throws ShopifyApiException
     */
    public function adjust(int $inventoryItemId, int $locationId, int $delta, bool $available = true): ShopifyInventoryLevelDto
    {
        $response = $this->client->post('inventory_levels/adjust.json', [
            'inventory_item_id' => $inventoryItemId,
            'location_id' => $locationId,
            'available_adjustment' => $delta,
            'available' => $available,
        ]);

        if (! isset($response['inventory_level']) || ! is_array($response['inventory_level'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on inventory adjust',
                endpoint: 'inventory_levels/adjust.json',
            );
        }

        $this->info('shopify.inventory.adjusted', [
            'inventory_item_id' => $inventoryItemId,
            'location_id' => $locationId,
            'delta' => $delta,
        ]);

        return ShopifyInventoryLevelDto::fromArray($response['inventory_level']);
    }

    /**
     * Connecte un inventory_item à une location (si pas encore connecté).
     *
     * @throws ShopifyApiException
     */
    public function connect(int $inventoryItemId, int $locationId, bool $relocateIfNecessary = false): ShopifyInventoryLevelDto
    {
        $response = $this->client->post('inventory_levels/connect.json', [
            'inventory_item_id' => $inventoryItemId,
            'location_id' => $locationId,
            'relocate_if_necessary' => $relocateIfNecessary,
        ]);

        if (! isset($response['inventory_level']) || ! is_array($response['inventory_level'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on inventory connect',
                endpoint: 'inventory_levels/connect.json',
            );
        }

        $this->info('shopify.inventory.connected', [
            'inventory_item_id' => $inventoryItemId,
            'location_id' => $locationId,
        ]);

        return ShopifyInventoryLevelDto::fromArray($response['inventory_level']);
    }

    /**
     * Définit une valeur absolue de stock (set au lieu de delta).
     *
     * @throws ShopifyApiException
     */
    public function set(int $inventoryItemId, int $locationId, int $available): ShopifyInventoryLevelDto
    {
        $response = $this->client->post('inventory_levels/set.json', [
            'inventory_item_id' => $inventoryItemId,
            'location_id' => $locationId,
            'available' => $available,
        ]);

        if (! isset($response['inventory_level']) || ! is_array($response['inventory_level'])) {
            throw new ShopifyApiException(
                message: 'Invalid Shopify response on inventory set',
                endpoint: 'inventory_levels/set.json',
            );
        }

        $this->info('shopify.inventory.set', [
            'inventory_item_id' => $inventoryItemId,
            'location_id' => $locationId,
            'available' => $available,
        ]);

        return ShopifyInventoryLevelDto::fromArray($response['inventory_level']);
    }
}
