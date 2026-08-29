<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            // Nullable : rempli seulement pour type = TABLE (RM11)
            $table->uuid('table_id')->nullable();
            $table->string('code')->unique();
            $table->string('url')->nullable();
            $table->string('image')->nullable();
            // TypeQRCode : TABLE, EMPORTER, LIVRAISON
            $table->string('type');
            $table->timestamp('date_expiration')->nullable();
            $table->integer('nombre_scan')->default(0);
            $table->boolean('est_actif')->default(true);
            $table->timestamp('created_at')->useCurrent(); // pas d'updated_at dans le diagramme

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->foreign('table_id')->references('id')->on('tables_restaurant')->cascadeOnDelete();
            $table->index(['code', 'est_actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};