<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_admin', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_id');
            $table->string('action');
            $table->string('table_cible')->nullable();
            $table->uuid('id_cible')->nullable();
            $table->text('ancienne_valeur')->nullable();
            $table->text('nouvelle_valeur')->nullable();
            $table->timestamp('date')->useCurrent();

            $table->foreign('admin_id')->references('id')->on('admin_utilisateurs')->cascadeOnDelete();
            $table->index(['table_cible', 'id_cible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_admin');
    }
};