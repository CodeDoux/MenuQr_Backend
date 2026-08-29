<?php

namespace App\Http\Requests;

use App\Models\Categorie;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProduitRequest extends FormRequest
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
            'prix' => ['required', 'numeric', 'min:0'],
            'est_disponible' => ['required', 'boolean'],
            'est_visible' => ['required', 'boolean'],
            'est_populaire' => ['required', 'boolean'],
            'temps_preparation' => ['nullable', 'integer', 'min:0'],

            'categorie_ids' => ['required', 'array', 'min:1'],
            'categorie_ids.*' => ['uuid'],

            'variantes' => ['nullable', 'array'],
            'variantes.*.nom' => ['required_with:variantes', 'string', 'max:255'],
            'variantes.*.prix' => ['required_with:variantes', 'numeric', 'min:0'],
            'variantes.*.est_disponible' => ['required_with:variantes', 'boolean'],

            'images' => ['nullable', 'array'],
            'images.*.url' => ['required_with:images', 'string', 'max:2048'],
            'images.*.ordre_affichage' => ['required_with:images', 'integer', 'min:1'],
            'images.*.est_principale' => ['required_with:images', 'boolean'],
        ];
    }

    /**
     * Vérifie que chaque catégorie référencée appartient bien, via son Menu,
     * au restaurant du contexte courant — 'exists:categories,id' seul ne
     * suffit pas puisque Categorie n'a pas de restaurant_id direct.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ids = $this->input('categorie_ids', []);
            if (empty($ids)) {
                return;
            }

            $tenant = app(TenantContext::class);
            $count = Categorie::whereIn('id', $ids)
                ->whereHas('menu', fn ($q) => $q->where('restaurant_id', $tenant->restaurantId))
                ->count();

            if ($count !== count(array_unique($ids))) {
                $validator->errors()->add('categorie_ids', 'Une ou plusieurs catégories sont invalides pour ce restaurant.');
            }
        });
    }
}