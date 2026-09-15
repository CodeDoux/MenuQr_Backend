<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->uuid('addition_id')->nullable()->after('visite_id');
            $table->foreign('addition_id')->references('id')->on('additions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropForeign(['addition_id']);
            $table->dropColumn('addition_id');
        });
    }
};