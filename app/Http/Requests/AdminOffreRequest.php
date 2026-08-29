<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminOffreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'prix_mensuel' => ['required', 'numeric', 'min:0'],
            'prix_annuel' => ['nullable', 'numeric', 'min:0'],
            'duree_essai' => ['nullable', 'integer', 'min:0'],
            'statut' => ['required', 'in:ACTIF,INACTIF,ARCHIVE'],
            'ordre_affichage' => ['required', 'integer', 'min:1'],
            'fonctionnalites' => ['nullable', 'array'],
            'fonctionnalites.*' => ['string', 'max:255'],
            'limites' => ['nullable', 'array'],
            'limites.*.nom' => ['required_with:limites', 'string'],
            'limites.*.valeur' => ['required_with:limites', 'integer'],
            'limites.*.unite' => ['required_with:limites', 'string'],
        ];
    }
}