<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->string('nom');
            // NULL = automatique, renseigné = le client doit le saisir
            $table->string('code')->nullable();
            // TypeReduction : POURCENTAGE, MONTANT_FIXE
            $table->string('type_reduction');
            $table->decimal('valeur', 10, 2);
            // CiblePromotion : PRODUIT, COMMANDE_ENTIERE
            $table->string('cible');
            $table->timestamp('date_debut');
            $table->timestamp('date_fin')->nullable();
            $table->integer('limite_utilisation')->nullable();
            $table->integer('nombre_utilisations')->default(0);
            $table->boolean('est_active')->default(true);
            $table->timestamp('created_at')->useCurrent(); // pas d'updated_at dans le diagramme

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->index(['restaurant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};