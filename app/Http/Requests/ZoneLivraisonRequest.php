<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ZoneLivraisonRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'frais' => ['required', 'numeric', 'min:0'],
            'temps_estime' => ['nullable', 'integer', 'min:0'],
            'distance_max' => ['nullable', 'numeric', 'min:0'],
            'statut' => ['required', 'in:ACTIVE,INACTIVE'],
        ];
    }
}