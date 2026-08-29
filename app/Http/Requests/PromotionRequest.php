<?php

namespace App\Http\Requests;

use App\Models\Produit;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PromotionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'type_reduction' => ['required', 'in:POURCENTAGE,MONTANT_FIXE'],
            'valeur' => ['required', 'numeric', 'min:0'],
            'cible' => ['required', 'in:PRODUIT,COMMANDE_ENTIERE'],
            'produit_ids' => ['required_if:cible,PRODUIT', 'array'],
            'produit_ids.*' => ['uuid'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
            'limite_utilisation' => ['nullable', 'integer', 'min:1'],
            'est_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ids = $this->input('produit_ids', []);
            if (empty($ids)) return;

            $tenant = app(TenantContext::class);
            $count = Produit::whereIn('id', $ids)->where('restaurant_id', $tenant->restaurantId)->count();

            if ($count !== count(array_unique($ids))) {
                $validator->errors()->add('produit_ids', 'Un ou plusieurs produits sont invalides pour ce restaurant.');
            }
        });
    }
}