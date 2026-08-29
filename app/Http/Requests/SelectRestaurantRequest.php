<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Vérification réelle faite dans le contrôleur (appartenance)
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'uuid'],
        ];
    }
}