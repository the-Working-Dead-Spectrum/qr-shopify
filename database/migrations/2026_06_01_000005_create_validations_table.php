<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('qr_code_id')
                  ->constrained('qr_codes')
                  ->cascadeOnDelete();

            $table->foreignId('partner_id')
                  ->constrained('partners')
                  ->cascadeOnDelete();

            $table->timestamp('scanned_at');

            $table->enum('status', ['valid', 'failed']);

            // IPv6 nécessite 45 caractères max
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Pas de updated_at — un scan est immuable
            $table->timestamp('created_at')->nullable();

            $table->index('qr_code_id');
            $table->index(['partner_id', 'scanned_at']); // Pour l'historique partenaire
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validations');
    }
};
