<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'nom_complet' => $this->nom_complet,
        'email' => $this->email,
        'email_verifie' => $this->email_verifie_le !== null,   // ← ajoutée
        'telephone' => $this->telephone,
        'photo' => $this->photo,
    ];
}
}