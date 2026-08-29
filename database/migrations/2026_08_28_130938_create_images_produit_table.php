<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images_produit', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('produit_id');
            $table->string('url');
            $table->integer('ordre_affichage')->default(1);
            $table->boolean('est_principale')->default(false);
            $table->timestamp('created_at')->useCurrent(); // pas d'updated_at dans le diagramme

            $table->foreign('produit_id')->references('id')->on('produits')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images_produit');
    }
};