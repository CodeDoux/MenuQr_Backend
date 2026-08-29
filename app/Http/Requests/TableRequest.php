<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero' => ['required', 'string', 'max:20'],
            'capacite' => ['required', 'integer', 'min:1'],
            'statut' => ['required', 'in:LIBRE,OCCUPEE,HORS_SERVICE'],
            'zone' => ['nullable', 'string', 'max:100'],
        ];
    }
}