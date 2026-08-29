<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Ajout validé : nécessaire pour l'isolation multi-tenant des
            // commandes directes (EMPORTER/LIVRAISON sans visite/table).
            $table->uuid('restaurant_id');
            $table->uuid('client_id')->nullable();
            $table->uuid('visite_id')->nullable();
            // Obligatoire seulement si mode = SUR_PLACE (contrainte applicative, RM06)
            $table->uuid('table_id')->nullable();
            // ModeCommande : SUR_PLACE, EMPORTER, LIVRAISON
            $table->string('mode');
            // StatutCommande : EN_ATTENTE, CONFIRMEE, EN_PREPARATION, PRETE, SERVIE, REMISE, LIVREE, ANNULEE
            $table->string('statut')->default('EN_ATTENTE');
            $table->decimal('sous_total', 10, 2);
            $table->decimal('frais_livraison', 10, 2)->default(0);
            $table->decimal('remise', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('visite_id')->references('id')->on('visites')->nullOnDelete();
            $table->foreign('table_id')->references('id')->on('tables_restaurant')->nullOnDelete();

            $table->index(['restaurant_id', 'statut']);
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};