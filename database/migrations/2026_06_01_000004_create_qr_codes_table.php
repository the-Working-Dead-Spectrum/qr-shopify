<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();

            // Hash HMAC-SHA256 de l'UUID — 64 caractères hex
            // C'est ce hash qui est stocké, jamais l'UUID brut
            $table->string('uuid', 256)->unique();

            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete();

            $table->enum('status', ['active', 'used', 'expired', 'revoked'])
                  ->default('active');

            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Partenaire ayant effectué la validation (null jusqu'au scan)
            $table->foreignId('partner_id')
                  ->nullable()
                  ->constrained('partners')
                  ->nullOnDelete();

            // Référence vers le QR précédent en cas de régénération
            $table->foreignId('regenerated_from')
                  ->nullable()
                  ->constrained('qr_codes')
                  ->nullOnDelete();

            $table->timestamps();

            // Index critiques pour les performances
            $table->index('status');
            $table->index('order_id');
            $table->index('expires_at');
            $table->index(['status', 'expires_at']); // Pour le job d'expiration
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
