<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'nom' => $this->nom, 'code' => $this->code,
            'type_reduction' => $this->type_reduction, 'valeur' => $this->valeur, 'cible' => $this->cible,
            'produit_ids' => $this->whenLoaded('produits', fn () => $this->produits->pluck('id')),
            'date_debut' => $this->date_debut, 'date_fin' => $this->date_fin,
            'limite_utilisation' => $this->limite_utilisation, 'nombre_utilisations' => $this->nombre_utilisations,
            'est_active' => $this->est_active, 'created_at' => $this->created_at,
        ];
    }
}
>