<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Correction #4 validée : pas de restaurant_id direct, relation
        // transitive uniquement via salle_id (évite une redondance à synchroniser).
        Schema::create('tables_restaurant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('salle_id');
            $table->string('numero');
            $table->integer('capacite');
            // StatutTable : LIBRE, OCCUPEE, HORS_SERVICE
            $table->string('statut')->default('LIBRE');
            $table->string('zone')->nullable();
            $table->timestamps();

            $table->foreign('salle_id')->references('id')->on('salles')->cascadeOnDelete();
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables_restaurant');
    }
};