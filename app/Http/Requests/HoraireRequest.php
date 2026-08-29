<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoraireRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'heure_ouverture' => ['nullable', 'date_format:H:i'],
            'heure_fermeture' => ['nullable', 'date_format:H:i', 'after:heure_ouverture'],
            'est_ferme' => ['required', 'boolean'],
        ];
    }
}