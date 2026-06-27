<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Jobs\BaseJob;
use App\Models\ShopifyWebhookEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Job de purge des anciens événements webhook Shopify.
 *
 * Stratégie :
 *  - Conservation 30 jours par défaut (config shopify.replay_protection.ttl_days)
 *  - Suppression par batch de 500 pour éviter les locks longs
 *  - Schedulé quotidiennement (routes/console.php)
 *
 * Note : Shopify peut rejouer un webhook des semaines après (cas rare),
 * donc on conserve large. Si l'espace disque devient critique, baisser
 * la TTL via config.
 */
final class PruneShopifyWebhookEventsJob extends BaseJob
{
    public int $tries = 1;

    public int $timeout = 600;

    public function handle(): void
    {
        $ttlDays = (int) config('shopify.replay_protection.ttl_days', 30);
        $cutoff = CarbonImmutable::now()->subDays($ttlDays);

        $deleted = 0;

        ShopifyWebhookEvent::query()
            ->olderThan($cutoff)
            ->chunkById(500, function ($events) use (&$deleted): void {
                foreach ($events as $event) {
                    $event->delete();
                    $deleted++;
                }
            });

        Log::info('[shopify.prune.webhook_events] completed', [
            'deleted_count' => $deleted,
            'cutoff' => $cutoff->toIso8601String(),
            'ttl_days' => $ttlDays,
        ]);
    }

    protected function failureContext(): array
    {
        return [
            'ttl_days' => (int) config('shopify.replay_protection.ttl_days', 30),
        ];
    }
}
