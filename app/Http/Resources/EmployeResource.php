<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $acces = $this->accesPlateforme;

        return [
            'id' => $this->id,
            'nom_complet' => $this->utilisateur?->nom_complet,
            'email' => $this->utilisateur?->email,
            'poste_id' => $this->poste_id,
            'poste_nom' => $this->poste?->nom,
            'matricule' => $this->matricule,
            'date_embauche' => $this->date_embauche,
            'date_fin' => $this->date_fin,
            'statut' => $this->statut,
            'notes' => $this->notes,
            'acces' => $acces ? [
                'id' => $acces->id,
                'statut' => $acces->statut,
                'role' => $acces->role?->code,
                'date_invitation' => $acces->date_invitation,
                'date_acceptation' => $acces->date_acceptation,
            ] : null,
        ];
    }
}