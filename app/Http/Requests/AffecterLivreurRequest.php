<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AffecterLivreurRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:EMPLOYE_RESTAURANT,PRESTATAIRE_EXTERNE,LIVREUR_CLIENT'],
            'employe_id' => ['required_if:type,EMPLOYE_RESTAURANT', 'nullable', 'uuid', 'exists:employes,id'],
            'nom' => ['required_unless:type,EMPLOYE_RESTAURANT', 'nullable', 'string', 'max:255'],
            'telephone' => ['required_unless:type,EMPLOYE_RESTAURANT', 'nullable', 'string', 'max:30'],
        ];
    }
}
