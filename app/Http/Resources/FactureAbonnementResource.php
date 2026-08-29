<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FactureAbonnementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'montant' => $this->montant,
            'date_emission' => $this->date_emission,
            'date_echeance' => $this->date_echeance,
            'statut' => $this->statut,
            'pdf' => $this->pdf,
        ];
    }
}