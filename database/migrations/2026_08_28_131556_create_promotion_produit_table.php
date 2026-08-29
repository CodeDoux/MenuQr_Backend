<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pertinent uniquement quand Promotion.cible = PRODUIT.
        Schema::create('promotion_produit', function (Blueprint $table) {
            $table->uuid('promotion_id');
            $table->uuid('produit_id');

            $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            $table->foreign('produit_id')->references('id')->on('produits')->cascadeOnDelete();

            $table->primary(['promotion_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_produit');
    }
};