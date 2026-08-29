<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Correction #1 validée : rattachement à Menu, pas directement à Restaurant
            $table->uuid('menu_id');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('icone')->nullable();
            $table->integer('ordre_affichage')->default(1);
            $table->boolean('est_active')->default(true);
            $table->timestamps();

            $table->foreign('menu_id')->references('id')->on('menus')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};