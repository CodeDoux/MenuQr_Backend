<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icone' => ['nullable', 'string', 'max:10'],
            'ordre_affichage' => ['required', 'integer', 'min:1'],
            'est_active' => ['required', 'boolean'],
        ];
    }
}
>