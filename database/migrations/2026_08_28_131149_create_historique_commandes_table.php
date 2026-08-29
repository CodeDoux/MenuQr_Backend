<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_commande', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('commande_id');
            $table->string('ancien_statut')->nullable();
            $table->string('nouveau_statut');
            $table->uuid('utilisateur_id')->nullable();
            $table->timestamp('date');
            $table->text('commentaire')->nullable();

            $table->foreign('commande_id')->references('id')->on('commandes')->cascadeOnDelete();
            $table->foreign('utilisateur_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_commande');
    }
};