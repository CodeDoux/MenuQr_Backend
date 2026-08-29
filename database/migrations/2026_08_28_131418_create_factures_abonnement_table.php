<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures_abonnement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('abonnement_id');
            $table->string('numero')->unique();
            $table->decimal('montant', 10, 2);
            $table->timestamp('date_emission');
            $table->timestamp('date_echeance');
            // StatutFactureAbonnement : EN_ATTENTE, PAYEE, EN_RETARD, ANNULEE
            $table->string('statut')->default('EN_ATTENTE');
            $table->string('pdf')->nullable();
            $table->timestamps();

            $table->foreign('abonnement_id')->references('id')->on('abonnements')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures_abonnement');
    }
};