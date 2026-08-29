<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class HoraireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'jour_semaine' => $this->jour_semaine,
            'heure_ouverture' => $this->heure_ouverture, 'heure_fermeture' => $this->heure_fermeture,
            'est_ferme' => $this->est_ferme,
        ];
    }
}
