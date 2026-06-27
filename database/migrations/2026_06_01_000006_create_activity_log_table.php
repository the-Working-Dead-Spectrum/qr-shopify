<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Ex: 'qr.revoked', 'partner.suspended', 'qr.resent'
            $table->string('action');

            // Polymorphisme manuel — plus léger que le package spatie/activity-log
            $table->string('subject_type');   // Ex: 'App\Models\QrCode'
            $table->unsignedBigInteger('subject_id');

            // Données contextuelles libres de l'action
            $table->json('properties')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
