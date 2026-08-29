<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->uuid('utilisateur_id');
            $table->uuid('poste_id');
            // Nullable : un employé n'a pas forcément d'accès à la plateforme (ex. Livreur en V1)
            $table->uuid('restaurant_utilisateur_id')->nullable();
            $table->string('matricule')->nullable();
            $table->date('date_embauche')->nullable();
            $table->date('date_fin')->nullable();
            // StatutEmploye : ACTIF, EN_CONGE, SUSPENDU, TERMINE
            $table->string('statut')->default('ACTIF');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->foreign('utilisateur_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('poste_id')->references('id')->on('postes')->restrictOnDelete();
            $table->foreign('restaurant_utilisateur_id')->references('id')->on('restaurant_utilisateur')->nullOnDelete();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};