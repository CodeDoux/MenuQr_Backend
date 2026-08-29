<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdditionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visite_id' => $this->visite_id,
            'table_numero' => $this->visite?->table?->numero,
            'sous_total' => $this->sous_total,
            'remise' => $this->remise,
            'taxe' => $this->taxe,
            'total' => $this->total,
            'statut' => $this->statut,
            'commandes' => CommandeResource::collection(
                $this->whenLoaded('visite', fn () => $this->visite->commandes()->with('lignes.produit')->get())
            ),
            'created_at' => $this->created_at,
        ];
    }
}
