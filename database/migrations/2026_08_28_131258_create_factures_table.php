<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->uuid('commande_id')->nullable();
            $table->uuid('addition_id')->nullable();
            $table->decimal('montant_ht', 10, 2);
            $table->decimal('taxe', 10, 2)->default(0);
            $table->decimal('montant_ttc', 10, 2);
            $table->timestamp('date_emission');
            // StatutFacture : EMISE, PAYEE, ANNULEE
            $table->string('statut')->default('EMISE');

            $table->foreign('commande_id')->references('id')->on('commandes')->nullOnDelete();
            $table->foreign('addition_id')->references('id')->on('additions')->nullOnDelete();
        });

        // Même règle d'exclusivité que Paiement (décision validée).
        DB::statement(<<<'SQL'
            ALTER TABLE factures ADD CONSTRAINT chk_facture_exclusivite CHECK (
                (commande_id IS NOT NULL AND addition_id IS NULL) OR
                (commande_id IS NULL AND addition_id IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};