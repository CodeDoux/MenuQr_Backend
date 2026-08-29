<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class MoyenPaiementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'methode' => $this->methode,
            'est_actif' => $this->est_actif, 'identifiant_marchand' => $this->identifiant_marchand,
        ];
    }
}
>