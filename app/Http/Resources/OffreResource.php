<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OffreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'prix_mensuel' => $this->prix_mensuel,
            'prix_annuel' => $this->prix_annuel,
            'devise' => $this->devise,
            'duree_essai' => $this->duree_essai,
        ];
    }
}