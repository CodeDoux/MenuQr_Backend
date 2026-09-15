<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ⚠️ 'exists:postes,id' seul vérifiait juste que l'UUID existe QUELQUE
        // PART en base — potentiellement le poste d'un AUTRE restaurant.
        // On restreint la vérification au restaurant courant.
        $restaurantId = app(TenantContext::class)->restaurantId;

        return [
            'nom_complet' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'poste_id' => [
                'required', 'uuid',
                Rule::exists('postes', 'id')->where('restaurant_id', $restaurantId),
            ],
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