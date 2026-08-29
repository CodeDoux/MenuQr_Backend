<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom_complet');
            $table->string('email')->unique();
            $table->string('telephone')->nullable();
            $table->string('password'); // motDePasse (hashé)
            $table->string('photo')->nullable();
            // StatutUtilisateur : ACTIF, INACTIF, BLOQUE, SUPPRIME (enum validé)
            $table->string('statut')->default('ACTIF');
            $table->boolean('email_verifie')->default(false);
            $table->boolean('telephone_verifie')->default(false);
            $table->timestamp('dernier_connexion')->nullable();
            $table->timestamps();
            $table->softDeletes(); // deletedAt du diagramme
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};