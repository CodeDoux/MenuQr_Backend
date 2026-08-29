<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProduitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'prix' => $this->prix,
            'est_disponible' => $this->est_disponible,
            'est_visible' => $this->est_visible,
            'est_populaire' => $this->est_populaire,
            'temps_preparation' => $this->temps_preparation,
            'statut' => $this->statut,
            'categorie_ids' => $this->whenLoaded('categories', fn () => $this->categories->pluck('id')),
            'variantes' => $this->whenLoaded('variantes', fn () => $this->variantes->map(fn ($v) => [
                'id' => $v->id,
                'nom' => $v->nom,
                'prix' => $v->prix,
                'est_disponible' => $v->est_disponible,
            ])),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'ordre_affichage' => $img->ordre_affichage,
                'est_principale' => $img->est_principale,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}