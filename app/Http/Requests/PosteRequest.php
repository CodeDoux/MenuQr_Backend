<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PosteRequest extends FormRequest
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
            'niveau' => ['nullable', 'integer'],
        ];
    }
}