<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LivraisonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commande_id' => $this->commande_id,
            'commande_total' => $this->commande?->total,
            'commande_statut' => $this->commande?->statut,
            'adresse_complete' => $this->adresse?->adresse_complete,
            'quartier' => $this->adresse?->quartier,
            'indications' => $this->adresse?->indications,
            'nom_client' => $this->nom_client,
            'telephone_client' => $this->telephone_client,
            'type_livreur' => $this->type_livreur,
            'livreur_nom' => $this->when(
                $this->type_livreur?->value === 'EMPLOYE_RESTAURANT',
                fn () => $this->livreurEmploye?->utilisateur?->nom_complet
            ),
            'nom_livreur_externe' => $this->nom_livreur_externe,
            'telephone_livreur_externe' => $this->telephone_livreur_externe,
            'statut' => $this->statut,
            'zone_nom' => $this->zoneLivraison?->nom,
            'date_affectation' => $this->date_affectation,
        ];
    }
}