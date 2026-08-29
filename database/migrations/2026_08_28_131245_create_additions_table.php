<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visite_id');
            $table->decimal('sous_total', 10, 2);
            $table->decimal('remise', 10, 2)->default(0);
            $table->decimal('taxe', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            // StatutAddition : OUVERTE, PARTIELLEMENT_PAYEE, PAYEE, ANNULEE
            $table->string('statut')->default('OUVERTE');
            $table->timestamp('created_at')->useCurrent(); // pas d'updated_at dans le diagramme

            $table->foreign('visite_id')->references('id')->on('visites')->cascadeOnDelete();
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additions');
    }
};