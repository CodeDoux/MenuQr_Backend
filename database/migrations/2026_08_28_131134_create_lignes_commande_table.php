<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_commande', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('commande_id');
            $table->uuid('produit_id');
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 10, 2);
            $table->decimal('sous_total', 10, 2);
            $table->text('notes')->nullable();

            $table->foreign('commande_id')->references('id')->on('commandes')->cascadeOnDelete();
            $table->foreign('produit_id')->references('id')->on('produits')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_commande');
    }
};
