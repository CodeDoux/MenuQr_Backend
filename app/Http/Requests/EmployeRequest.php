<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_complet' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'poste_id' => ['required', 'uuid', 'exists:postes,id'],
            'matricule' => ['nullable', 'string', 'max:50'],
            'date_embauche' => ['nullable', 'date'],
            'statut' => ['required', 'in:ACTIF,EN_CONGE,SUSPENDU'],
            'notes' => ['nullable', 'string'],
            'accorder_acces' => ['required', 'boolean'],
            // PROPRIETAIRE volontairement exclu : rôle réservé au créateur du compte
            'role' => ['required_if:accorder_acces,true', 'nullable', 'in:GERANT,SERVEUR,CUISINIER,CAISSIER,LIVREUR'],
        ];
    }
}