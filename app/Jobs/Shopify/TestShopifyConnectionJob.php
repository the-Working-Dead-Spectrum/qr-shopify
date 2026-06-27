<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Jobs\BaseJob;
use App\Services\Shopify\ShopifyClient;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Job de test de connectivité Shopify.
 *
 * Utilisé par :
 *  - La commande artisan `shopify:test-connection`
 *  - Le healthcheck du dashboard admin
 *  - Les tests d'intégration
 *
 * Le job appelle /admin/api/{version}/shop.json qui est léger et
 * toujours disponible. Échec → retry exponentiel ; définitif → log CRITICAL.
 */
final class TestShopifyConnectionJob extends BaseJob
{
    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(
        public readonly bool $logPayload = false,
    ) {}

    public function handle(ShopifyClient $client): void
    {
        $response = $client->get('shop.json');

        $shop = $response['shop'] ?? null;

        if ($shop === null) {
            Log::error('[shopify.test_connection] invalid_response', [
                'response_keys' => array_keys($response),
            ]);

            throw new RuntimeException('Shopify returned empty shop object');
        }

        Log::info('[shopify.test_connection] success', [
            'shop_domain' => $shop['domain'] ?? null,
            'shop_name' => $shop['name'] ?? null,
            'plan' => $shop['plan_display_name'] ?? null,
        ]);
    }

    protected function failureContext(): array
    {
        return [
            'log_payload' => $this->logPayload,
        ];
    }
}
