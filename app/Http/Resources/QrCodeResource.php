<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QrCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'table_id' => $this->table_id,
            'code' => $this->code,
            'url' => $this->url,
            'image' => $this->image,
            'type' => $this->type,
            'date_expiration' => $this->date_expiration,
            'nombre_scan' => $this->nombre_scan,
            'est_actif' => $this->est_actif,
            'created_at' => $this->created_at,
        ];
    }
}