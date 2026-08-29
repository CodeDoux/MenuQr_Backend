<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->uuid('offre_id');
            $table->timestamp('date_debut');
            $table->timestamp('date_fin');
            // StatutAbonnement : ESSAI, ACTIF, EXPIRE, SUSPENDU, ANNULE
            $table->string('statut')->default('ESSAI');
            $table->boolean('renouvellement_automatique')->default(false);
            $table->timestamp('date_prochain_paiement')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->foreign('offre_id')->references('id')->on('offres')->restrictOnDelete();
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};