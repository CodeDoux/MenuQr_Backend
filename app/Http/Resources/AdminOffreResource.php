<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOffreResource extends JsonResource
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
            'statut' => $this->statut,
            'ordre_affichage' => $this->ordre_affichage,
            'fonctionnalites' => $this->whenLoaded('fonctionnalites', fn () => $this->fonctionnalites->pluck('nom')),
            'limites' => $this->whenLoaded('limites', fn () => $this->limites->map(fn ($l) => [
                'nom' => $l->nom, 'valeur' => $l->valeur, 'unite' => $l->unite,
            ])),
        ];
    }
}