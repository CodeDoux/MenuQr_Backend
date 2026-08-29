<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livraisons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('commande_id');
            $table->uuid('adresse_id');
            $table->string('nom_client');
            $table->string('telephone_client');
            $table->text('instructions')->nullable();
            // TypeLivreur : EMPLOYE_RESTAURANT, PRESTATAIRE_EXTERNE, LIVREUR_CLIENT
            // Nullable tant que la livraison n'est pas encore affectée.
            $table->string('type_livreur')->nullable();
            $table->uuid('livreur_employe_id')->nullable();
            $table->string('nom_livreur_externe')->nullable();
            $table->string('telephone_livreur_externe')->nullable();
            // StatutLivraison : EN_ATTENTE_AFFECTATION, AFFECTEE, RECUPEREE, EN_ROUTE, LIVREE, ANNULEE
            $table->string('statut')->default('EN_ATTENTE_AFFECTATION');
            $table->uuid('zone_livraison_id')->nullable();
            $table->timestamp('date_affectation')->nullable();
            $table->timestamps();

            $table->foreign('commande_id')->references('id')->on('commandes')->cascadeOnDelete();
            $table->foreign('adresse_id')->references('id')->on('adresses_livraison')->restrictOnDelete();
            $table->foreign('livreur_employe_id')->references('id')->on('employes')->nullOnDelete();
            $table->foreign('zone_livraison_id')->references('id')->on('zones_livraison')->nullOnDelete();

            $table->unique('commande_id'); // Commande 1 --- 0..1 Livraison
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livraisons');
    }
};