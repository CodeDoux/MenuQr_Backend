<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('logo')->nullable();
            $table->string('adresse');
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            // StatutRestaurant : ACTIF, SUSPENDU, INACTIF, FERME (enum validé)
            $table->string('statut')->default('ACTIF');
            $table->timestamps();
 
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
