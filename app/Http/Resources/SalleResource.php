<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'ordre' => $this->ordre,
            'statut' => $this->statut,
            'tables_count' => $this->whenCounted('tables'),
        ];
    }
}