<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoyenPaiementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'est_actif' => ['required', 'boolean'],
            'identifiant_marchand' => ['nullable', 'string', 'max:255'],
        ];
    }
}