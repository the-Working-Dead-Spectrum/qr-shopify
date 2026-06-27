<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('name');

            $table->string('slug')->unique();

            $table->enum('status', ['active', 'inactive', 'suspended'])
                  ->default('active');

            // Compteur quotidien d'appels API — remis à zéro chaque jour par le scheduler
            $table->unsignedInteger('api_calls_today')->default(0);

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
