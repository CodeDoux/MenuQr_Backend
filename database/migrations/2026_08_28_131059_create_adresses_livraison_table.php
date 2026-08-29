<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adresses_livraison', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Nullable : couvre le cas d'une adresse saisie par un client anonyme
            // (sans compte), créée à la volée pour une seule commande.
            $table->uuid('client_id')->nullable();
            $table->string('nom_adresse')->nullable();
            $table->string('adresse_complete');
            $table->string('quartier')->nullable();
            $table->string('ville')->nullable();
            $table->string('region')->nullable();
            $table->text('indications')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adresses_livraison');
    }
};