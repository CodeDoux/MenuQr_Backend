<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FactureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'commande_id' => $this->commande_id,
            'addition_id' => $this->addition_id,
            'montant_ht' => $this->montant_ht,
            'taxe' => $this->taxe,
            'montant_ttc' => $this->montant_ttc,
            'date_emission' => $this->date_emission,
            'statut' => $this->statut,
            'lignes' => $this->when(
                $this->commande_id || $this->addition_id,
                fn () => $this->resolveLignesPourImpression()
            ),
        ];
    }

    private function resolveLignesPourImpression(): array
    {
        if ($this->commande_id && $this->commande) {
            return LigneCommandeResource::collection($this->commande->lignes()->with('produit')->get())->toArray(request());
        }
        if ($this->addition_id && $this->addition && $this->addition->visite) {
            $lignes = collect();
            foreach ($this->addition->visite->commandes as $commande) {
                $lignes = $lignes->merge($commande->lignes()->with('produit')->get());
            }
            return LigneCommandeResource::collection($lignes)->toArray(request());
        }
        return [];
    }
}