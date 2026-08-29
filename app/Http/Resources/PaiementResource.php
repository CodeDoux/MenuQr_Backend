<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaiementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'commande_id' => $this->commande_id,
            'addition_id' => $this->addition_id,
            'montant' => $this->montant,
            'devise' => $this->devise,
            'methode' => $this->methode,
            'statut' => $this->statut,
            'reference' => $this->reference,
            'date_paiement' => $this->date_paiement,
        ];
    }
}