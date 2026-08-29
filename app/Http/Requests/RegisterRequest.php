<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Inscription libre-service (décision actée)
    }

    public function rules(): array
    {
        return [
            'nom_complet' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'restaurant_nom' => ['required', 'string', 'max:255'],
            'restaurant_adresse' => ['required', 'string', 'max:255'],
            'restaurant_telephone' => ['required', 'string', 'max:30'],
            'offre_id' => ['required', 'uuid', 'exists:offres,id'],
        ];
    }
}