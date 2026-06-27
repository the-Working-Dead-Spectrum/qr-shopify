<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de monitoring des webhooks Shopify.
     *
     * Stocke les statistiques agrégées pour le dashboard admin.
     * Alimentée par le WebhookMonitorService après chaque traitement.
     *
     * Champs :
     * - date : jour calendaire (PK composite).
     * - topic : topic du webhook.
     * - received_count / processed_count / failed_count / replay_count.
     * - avg_processing_ms : temps moyen de traitement en millisecondes.
     * - retry_count : nombre cumulé de retries (HTTP 5xx, timeouts).
     */
    public function up(): void
    {
        Schema::create('shopify_webhook_stats', function (Blueprint $table): void {
            $table->id();

            $table->date('date');
            $table->string('topic', 64);
            $table->string('shop_domain', 128)->nullable();

            $table->unsignedBigInteger('received_count')->default(0);
            $table->unsignedBigInteger('processed_count')->default(0);
            $table->unsignedBigInteger('failed_count')->default(0);
            $table->unsignedBigInteger('replay_count')->default(0);
            $table->unsignedBigInteger('retry_count')->default(0);

            // Temps moyen de traitement en ms (utilisé pour alertes SLO)
            $table->unsignedInteger('avg_processing_ms')->default(0);
            $table->unsignedInteger('p95_processing_ms')->default(0);

            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // PK composite : un enregistrement unique par (date, topic, shop)
            $table->unique(['date', 'topic', 'shop_domain'], 'shopify_webhook_stats_pk');

            // Index pour le dashboard (récupération rapide des X derniers jours)
            $table->index(['date', 'topic'], 'shopify_webhook_stats_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_webhook_stats');
    }
};