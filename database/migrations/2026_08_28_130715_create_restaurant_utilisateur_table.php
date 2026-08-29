<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_utilisateur', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->uuid('utilisateur_id');
            $table->uuid('role_id');
            // StatutAcces : INVITE, ACTIF, SUSPENDU, REVOQUE (enum validé)
            $table->string('statut')->default('INVITE');
            $table->timestamp('date_invitation')->nullable();
            $table->timestamp('date_acceptation')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->foreign('utilisateur_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();

            // Un utilisateur ne peut avoir qu'un seul accès actif par restaurant
            $table->unique(['restaurant_id', 'utilisateur_id']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_utilisateur');
    }
};