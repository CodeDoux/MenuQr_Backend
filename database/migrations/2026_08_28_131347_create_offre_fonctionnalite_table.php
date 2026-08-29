<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offre_fonctionnalite', function (Blueprint $table) {
            $table->uuid('offre_id');
            $table->uuid('fonctionnalite_id');

            $table->foreign('offre_id')->references('id')->on('offres')->cascadeOnDelete();
            $table->foreign('fonctionnalite_id')->references('id')->on('fonctionnalites')->cascadeOnDelete();

            $table->primary(['offre_id', 'fonctionnalite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offre_fonctionnalite');
    }
};