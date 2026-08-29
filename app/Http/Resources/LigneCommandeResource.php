<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LigneCommandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produit_id' => $this->produit_id,
            'produit_nom' => $this->produit?->nom,
            'quantite' => $this->quantite,
            'prix_unitaire' => $this->prix_unitaire,
            'sous_total' => $this->sous_total,
            'notes' => $this->notes,
        ];
    }
}
