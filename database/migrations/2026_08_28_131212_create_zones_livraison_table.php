<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones_livraison', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('frais', 10, 2);
            $table->integer('temps_estime')->nullable(); // minutes
            $table->decimal('distance_max', 6, 2)->nullable(); // km
            // StatutZone : ACTIVE, INACTIVE
            $table->string('statut')->default('ACTIVE');
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones_livraison');
    }
};