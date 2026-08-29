<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'image' => $this->image,
            'ordre_affichage' => $this->ordre_affichage,
            'est_actif' => $this->est_actif,
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'categories_count' => $this->whenCounted('categories'),
            'categories' => CategorieResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}