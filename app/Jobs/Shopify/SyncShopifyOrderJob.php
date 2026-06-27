<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Contracts\ShopifyServiceInterface;
use App\Exceptions\Shopify\ShopifyApiException;
use App\Jobs\BaseJob;
use App\Services\Shopify\ShopifyClient;
use Illuminate\Support\Facades\Log;

/**
 * Job de synchronisation d'une commande Shopify (sortant).
 *
 * Cas d'usage :
 *  - Récupérer les détails complets d'une commande pour mise à jour
 *  - Synchroniser après une période d'inactivité (cron quotidien)
 *  - Enrichir une commande créée par webhook avec champs manquants
 *
 * Retry :
 *  - 5 tentatives max
 *  - Backoff exponentiel 30s, 1min, 2min, 5min, 10min
 *  - Échec définitif → alerte admin (NotifyAdminOnErrorJob via BaseJob::failed)
 */
final class SyncShopifyOrderJob extends BaseJob
{
    /**
     * Backoff spécifique pour ce Job.
     * Plus court que GenerateAndSendQrJob car c'est de la sync.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600];
    }

    public function __construct(
        public readonly string $shopifyOrderId,
        public readonly ?string $shopDomain = null,
    ) {}

    public function handle(ShopifyClient $client): void
    {
        try {
            $response = $client->get('orders/'.$this->shopifyOrderId.'.json');

            $order = $response['order'] ?? null;

            if ($order === null) {
                Log::warning('[shopify.sync.order] not_found', [
                    'shopify_order_id' => $this->shopifyOrderId,
                ]);

                return;
            }

            // Mise à jour en BDD via le service (idempotent)
            app(ShopifyServiceInterface::class)
                ->processOrderUpdated($order);

            Log::info('[shopify.sync.order] synced', [
                'shopify_order_id' => $this->shopifyOrderId,
            ]);
        } catch (ShopifyApiException $e) {
            if (! $e->isRetryable()) {
                Log::error('[shopify.sync.order] non_retryable_error', [
                    'shopify_order_id' => $this->shopifyOrderId,
                    'status' => $e->statusCode,
                    'error' => $e->getMessage(),
                ]);
                $this->fail($e); // Échec définitif sans retry

                return;
            }

            Log::warning('[shopify.sync.order] retryable_error', [
                'shopify_order_id' => $this->shopifyOrderId,
                'status' => $e->statusCode,
                'attempt' => $this->attempts(),
            ]);

            throw $e; // → retry
        }
    }

    /**
     * Contexte pour NotifyAdminOnErrorJob.
     *
     * @return array<string, mixed>
     */
    protected function failureContext(): array
    {
        return [
            'shopify_order_id' => $this->shopifyOrderId,
            'shop_domain' => $this->shopDomain,
        ];
    }
}
