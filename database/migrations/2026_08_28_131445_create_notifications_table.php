<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('utilisateur_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->string('titre');
            $table->text('message');
            // TypeNotification : NOUVELLE_COMMANDE, STATUT_COMMANDE_CHANGE,
            // ABONNEMENT_EXPIRE_BIENTOT, STOCK_RUPTURE, NOUVEL_EMPLOYE_INVITE
            $table->string('type');
            $table->string('lien')->nullable();
            $table->boolean('est_lu')->default(false);
            $table->timestamp('date_envoie')->useCurrent();

            $table->foreign('utilisateur_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->index(['utilisateur_id', 'est_lu']);
        });

        // Une notification s'adresse soit à un membre du staff, soit à un client.
        DB::statement(<<<'SQL'
            ALTER TABLE notifications ADD CONSTRAINT chk_notification_destinataire CHECK (
                (utilisateur_id IS NOT NULL AND client_id IS NULL) OR
                (utilisateur_id IS NULL AND client_id IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};