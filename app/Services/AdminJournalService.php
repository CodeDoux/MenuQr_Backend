<?php

namespace App\Services;

use App\Models\JournalAdmin;

/**
 * Journal des actions Admin — distinct du journal restaurant (JournalService),
 * puisque l'acteur ici est un AdminUtilisateur (contrôle de toute la
 * plateforme), pas un utilisateur restaurant. Toute action sensible
 * (suspendre un restaurant, créer une offre, gérer d'autres admins,
 * usurper un compte) doit être traçable.
 */
class AdminJournalService
{
    public function enregistrer(
        string $adminId,
        string $action,
        ?string $tableCible = null,
        ?string $idCible = null,
        mixed $ancienneValeur = null,
        mixed $nouvelleValeur = null
    ): void {
        JournalAdmin::create([
            'admin_id' => $adminId,
            'action' => $action,
            'table_cible' => $tableCible,
            'id_cible' => $idCible,
            'ancienne_valeur' => is_array($ancienneValeur) ? json_encode($ancienneValeur) : $ancienneValeur,
            'nouvelle_valeur' => is_array($nouvelleValeur) ? json_encode($nouvelleValeur) : $nouvelleValeur,
            'date' => now(),
        ]);
    }
}