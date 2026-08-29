<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table separee des restaurants (users) : un compte admin MenuQR n'a aucun
 * lien avec un restaurant precis, il voit toute la plateforme. Authentifie
 * via les memes tokens Sanctum (polymorphes) mais jamais avec restaurant_id
 * renseigne sur son token.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_utilisateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom_complet');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_utilisateurs');
    }
};