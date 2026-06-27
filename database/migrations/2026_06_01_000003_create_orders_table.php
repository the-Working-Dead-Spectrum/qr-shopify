<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Identifiant Shopify — UNIQUE pour garantir l'idempotence des webhooks
            $table->string('shopify_order_id')->unique();

            $table->string('customer_email', 255);
            $table->string('customer_name', 255)->nullable();

            // Montant en centimes pour éviter les erreurs d'arrondi float
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 10)->default('EUR');

            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');

            $table->timestamps();

            // Index composite pour les filtres du dashboard admin
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
