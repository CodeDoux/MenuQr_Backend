<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'message' => $this->message,
            'type' => $this->type,
            'lien' => $this->lien,
            'est_lu' => $this->est_lu,
            'date_envoie' => $this->date_envoie,
        ];
    }
}