<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $abonnement = $this->abonnements()->latest('date_debut')->with('offre')->first();

        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'statut' => $this->statut,
            'created_at' => $this->created_at,
            'offre_nom' => $abonnement?->offre?->nom,
            'abonnement_statut' => $abonnement?->statut,
            'date_fin_abonnement' => $abonnement?->date_fin,
        ];
    }
}