<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('limites_plan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('offre_id');
            $table->string('nom'); // ex. "nombre_tables"
            $table->integer('valeur'); // -1 = illimité (convention)
            $table->string('unite');

            $table->foreign('offre_id')->references('id')->on('offres')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('limites_plan');
    }
};