<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de protection contre le replay attack des webhooks Shopify.
     *
     * Shopify envoie un header `X-Shopify-Webhook-Id` unique par webhook.
     * On le persiste ici pour rejeter toute tentative de rejeu (DDoS,
     * bug côté Shopify, retry malveillant).
     *
     * Champs :
     * - id : PK technique.
     * - webhook_id : valeur du header X-Shopify-Webhook-Id (UNIQUE).
     * - topic : topic du webhook (orders/paid, orders/cancelled, ...).
     * - shop_domain : shop ayant envoyé le webhook (sécurité multi-boutique).
     * - payload_hash : SHA256 du payload brut — permet de tracer un rejeu
     *                 même si l'ID est régénéré (très rare mais possible).
     * - order_id : référence métier optionnelle (FK).
     * - status : received / processed / failed.
     * - received_at / processed_at : horodatage exploitation + purge.
     *
     * Nettoyage : job planifié `PruneShopifyWebhookEventsJob` (cron mensuel).
     */
    public function up(): void
    {
        Schema::create('shopify_webhook_events', function (Blueprint $table): void {
            $table->id();

            // Identifiant unique du webhook (header X-Shopify-Webhook-Id)
            // 128 chars max observé sur Shopify.
            $table->string('webhook_id', 128);
            $table->string('topic', 64);
            $table->string('shop_domain', 128)->nullable();
            $table->string('payload_hash', 64)->nullable();

            // Statut de traitement
            $table->enum('status', ['received', 'processed', 'failed', 'skipped'])
                  ->default('received');

            // Contexte métier optionnel
            $table->string('shopify_order_id')->nullable();

            $table->json('meta')->nullable();

            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();

            // ===== INDEX =====
            // UNIQUE sur webhook_id : garantit l'idempotence et accélère la lookup
            $table->unique('webhook_id', 'shopify_webhook_events_id_unique');

            // Topic + received_at pour les stats dashboard
            $table->index(['topic', 'received_at'], 'shopify_webhook_events_topic_idx');

            // Status pour le job de nettoyage
            $table->index(['status', 'received_at'], 'shopify_webhook_events_status_idx');

            // Pour le job de purge (received_at < now() - X days)
            $table->index('received_at', 'shopify_webhook_events_received_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_webhook_events');
    }
};