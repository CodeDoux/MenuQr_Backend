<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Correction #2 validée : appartenance directe au restaurant (isolation multi-tenant)
            $table->uuid('restaurant_id');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('prix', 10, 2);
            $table->boolean('est_disponible')->default(true);
            $table->boolean('est_visible')->default(true);
            $table->boolean('est_populaire')->default(false);
            $table->integer('temps_preparation')->nullable(); // minutes
            // StatutProduit : ACTIF, ARCHIVE
            $table->string('statut')->default('ACTIF');
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};