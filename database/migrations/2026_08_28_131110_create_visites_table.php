<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->uuid('client_id')->nullable();
            $table->uuid('table_id')->nullable();
            $table->timestamp('date_debut');
            $table->timestamp('date_fin')->nullable();
            // StatutVisite : EN_COURS, TERMINEE
            $table->string('statut')->default('EN_COURS');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('table_id')->references('id')->on('tables_restaurant')->nullOnDelete();
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visites');
    }
};