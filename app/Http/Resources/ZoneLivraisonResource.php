<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneLivraisonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'frais' => $this->frais,
            'temps_estime' => $this->temps_estime,
            'distance_max' => $this->distance_max,
            'statut' => $this->statut,
        ];
    }
}
