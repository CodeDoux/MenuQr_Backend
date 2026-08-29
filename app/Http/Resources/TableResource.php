<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'salle_id' => $this->salle_id,
            'numero' => $this->numero,
            'capacite' => $this->capacite,
            'statut' => $this->statut,
            'zone' => $this->zone,
        ];
    }
}