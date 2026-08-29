<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EncaisserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'methode' => ['required', 'in:ESPECES,WAVE,ORANGE_MONEY,CARTE,AUTRE'],
        ];
    }
}