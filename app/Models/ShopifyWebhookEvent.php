<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle représentant un webhook Shopify reçu.
 *
 * Utilisé pour :
 *  - protection replay (UNIQUE sur webhook_id)
 *  - monitoring (compteurs agrégés)
 *  - audit / debug
 *
 * Cycle de vie :
 *  1. INSERT (status=received) dans VerifyShopifyHmac middleware
 *  2. UPDATE status=processed|failed après traitement
 *  3. DELETE par job planifié après TTL
 */
class ShopifyWebhookEvent extends Model
{
    public $timestamps = false;

    protected $table = 'shopify_webhook_events';

    protected $fillable = [
        'webhook_id',
        'topic',
        'shop_domain',
        'payload_hash',
        'status',
        'shopify_order_id',
        'meta',
        'received_at',
        'processed_at',
    ];

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOlderThan($query, CarbonInterface $cutoff)
    {
        return $query->where('received_at', '<', $cutoff);
    }

    public function scopeForTopic($query, string $topic)
    {
        return $query->where('topic', $topic);
    }

    // -------------------------------------------------------------------------
    // Helpers métier
    // -------------------------------------------------------------------------

    public function markProcessed(?string $shopifyOrderId = null): void
    {
        $this->forceFill([
            'status' => 'processed',
            'processed_at' => now(),
            'shopify_order_id' => $shopifyOrderId ?? $this->shopify_order_id,
        ])->save();
    }

    public function markFailed(string $reason): void
    {
        $this->forceFill([
            'status' => 'failed',
            'processed_at' => now(),
            'meta' => array_merge($this->meta ?? [], ['failure_reason' => $reason]),
        ])->save();
    }

    public function markSkipped(string $reason): void
    {
        $this->forceFill([
            'status' => 'skipped',
            'processed_at' => now(),
            'meta' => array_merge($this->meta ?? [], ['skip_reason' => $reason]),
        ])->save();
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
