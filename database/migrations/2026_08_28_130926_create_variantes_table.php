<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variantes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('produit_id');
            $table->string('nom');
            $table->decimal('prix', 10, 2);
            $table->boolean('est_disponible')->default(true);

            $table->foreign('produit_id')->references('id')->on('produits')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variantes');
    }
};