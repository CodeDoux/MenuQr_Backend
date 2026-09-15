<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('nom_client')->nullable()->after('notes');
            $table->string('telephone_client', 30)->nullable()->after('nom_client');
            $table->timestamp('heure_retrait_souhaitee')->nullable()->after('telephone_client');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['nom_client', 'telephone_client', 'heure_retrait_souhaitee']);
        });
    }
};