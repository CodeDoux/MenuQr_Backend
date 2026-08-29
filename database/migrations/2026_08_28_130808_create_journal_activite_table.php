<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_activite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('utilisateur_id');
            $table->uuid('restaurant_id');
            $table->string('action');
            $table->string('table_cible');
            $table->uuid('id_cible')->nullable();
            $table->jsonb('ancienne_valeur')->nullable();
            $table->jsonb('nouvelle_valeur')->nullable();
            $table->timestamp('date')->useCurrent();

            $table->foreign('utilisateur_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->index(['restaurant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_activite');
    }
};