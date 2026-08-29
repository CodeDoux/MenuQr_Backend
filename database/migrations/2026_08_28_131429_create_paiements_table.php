<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // TypePaiement : COMMANDE, ABONNEMENT
            $table->string('type');
            $table->uuid('commande_id')->nullable();
            $table->uuid('addition_id')->nullable();
            $table->uuid('facture_abonnement_id')->nullable();
            $table->decimal('montant', 10, 2);
            $table->string('devise')->default('FCFA');
            // MethodePaiement : ESPECES, WAVE, ORANGE_MONEY, CARTE, AUTRE
            $table->string('methode');
            // StatutPaiement : EN_ATTENTE, CONFIRME, ECHOUE, REMBOURSE, ANNULE
            $table->string('statut')->default('EN_ATTENTE');
            $table->string('reference')->nullable();
            $table->timestamp('date_paiement')->nullable();
            $table->timestamps();

            $table->foreign('commande_id')->references('id')->on('commandes')->nullOnDelete();
            $table->foreign('addition_id')->references('id')->on('additions')->nullOnDelete();
            $table->foreign('facture_abonnement_id')->references('id')->on('factures_abonnement')->nullOnDelete();

            $table->index(['type', 'statut']);
        });

        // Règle métier validée (Correction #3) : un paiement COMMANDE référence
        // exactement l'un des deux (commande_id XOR addition_id), jamais les deux ;
        // un paiement ABONNEMENT référence uniquement facture_abonnement_id.
        // Contrainte posée au niveau base — pas seulement applicatif.
        DB::statement(<<<'SQL'
            ALTER TABLE paiements ADD CONSTRAINT chk_paiement_exclusivite CHECK (
                (type = 'COMMANDE' AND facture_abonnement_id IS NULL AND (
                    (commande_id IS NOT NULL AND addition_id IS NULL) OR
                    (commande_id IS NULL AND addition_id IS NOT NULL)
                ))
                OR
                (type = 'ABONNEMENT' AND commande_id IS NULL AND addition_id IS NULL AND facture_abonnement_id IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};