<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorie_produit', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('categorie_id');
            $table->uuid('produit_id');
            $table->integer('ordre_affichage')->default(1);

            $table->foreign('categorie_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->foreign('produit_id')->references('id')->on('produits')->cascadeOnDelete();

            $table->unique(['categorie_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorie_produit');
    }
};