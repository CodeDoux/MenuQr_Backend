<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbonnementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'offre' => new OffreResource($this->whenLoaded('offre')),
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'statut' => $this->statut,
            'renouvellement_automatique' => $this->renouvellement_automatique,
            'date_prochain_paiement' => $this->date_prochain_paiement,
        ];
    }
}