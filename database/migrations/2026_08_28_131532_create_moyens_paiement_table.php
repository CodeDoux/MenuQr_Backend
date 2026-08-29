<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moyens_paiement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            // MethodePaiement : ESPECES, WAVE, ORANGE_MONEY, CARTE, AUTRE
            $table->string('methode');
            $table->boolean('est_actif')->default(true);
            $table->string('identifiant_marchand')->nullable();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->unique(['restaurant_id', 'methode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moyens_paiement');
    }
};