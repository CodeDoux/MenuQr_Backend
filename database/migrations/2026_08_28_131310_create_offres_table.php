<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pas de restaurant_id : les offres sont globales à la plateforme,
        // gérées par l'Admin MenuQR, proposées à tous les restaurants.
        Schema::create('offres', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('prix_mensuel', 10, 2);
            $table->decimal('prix_annuel', 10, 2)->nullable();
            $table->string('devise')->default('FCFA');
            $table->integer('duree_essai')->nullable(); // jours
            // StatutPlan : ACTIF, INACTIF, ARCHIVE
            $table->string('statut')->default('ACTIF');
            $table->integer('ordre_affichage')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offres');
    }
};