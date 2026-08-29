<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'ordre' => ['required', 'integer', 'min:1'],
            'statut' => ['required', 'in:ACTIVE,INACTIVE'],
        ];
    }
}