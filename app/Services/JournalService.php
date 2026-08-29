<?php

namespace App\Services;

use App\Models\JournalActivite;

class JournalService
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function enregistrer(string $action, string $tableCible, ?string $idCible = null, $ancienneValeur = null, $nouvelleValeur = null): void
    {
        JournalActivite::create([
            'restaurant_id' => $this->tenant->restaurantId,
            'utilisateur_id' => auth()->id(),
            'action' => $action,
            'table_cible' => $tableCible,
            'id_cible' => $idCible,
            'ancienne_valeur' => $ancienneValeur,
            'nouvelle_valeur' => $nouvelleValeur,
            'date' => now(),
        ]);
    }
}