<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visite_id' => $this->visite_id,
            'table_id' => $this->table_id,
            'table_numero' => $this->table?->numero,
            'mode' => $this->mode,
            'statut' => $this->statut,
            'sous_total' => $this->sous_total,
            'frais_livraison' => $this->frais_livraison,
            'remise' => $this->remise,
            'total' => $this->total,
            'notes' => $this->notes,
            'nom_client' => $this->nom_client,
            'telephone_client' => $this->telephone_client,
            'heure_retrait_souhaitee' => $this->heure_retrait_souhaitee,
            'lignes' => LigneCommandeResource::collection($this->whenLoaded('lignes')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}