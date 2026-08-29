<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreerCommandePubliqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'mode' => ['required', 'in:SUR_PLACE,EMPORTER,LIVRAISON'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produit_id' => ['required', 'uuid'],
            'items.*.variante_id' => ['nullable', 'uuid'],
            'items.*.quantite' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            // Requis uniquement si mode = LIVRAISON
            'nom_client' => ['required_if:mode,LIVRAISON', 'nullable', 'string', 'max:255'],
            'telephone_client' => ['required_if:mode,LIVRAISON', 'nullable', 'string', 'max:30'],
            'adresse_complete' => ['required_if:mode,LIVRAISON', 'nullable', 'string', 'max:500'],
            'quartier' => ['nullable', 'string', 'max:255'],
            'indications' => ['nullable', 'string'],
            'zone_livraison_id' => ['required_if:mode,LIVRAISON', 'nullable', 'uuid'],
        ];
    }
}
>