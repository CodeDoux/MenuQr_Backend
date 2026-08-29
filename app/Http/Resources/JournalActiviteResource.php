<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class JournalActiviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'utilisateur_nom' => $this->utilisateur?->nom_complet,
            'action' => $this->action, 'table_cible' => $this->table_cible, 'id_cible' => $this->id_cible,
            'ancienne_valeur' => $this->ancienne_valeur, 'nouvelle_valeur' => $this->nouvelle_valeur,
            'date' => $this->date,
        ];
    }
}
>