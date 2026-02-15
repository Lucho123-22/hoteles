<?php

namespace App\Http\Requests\RateType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRateTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar según tus políticas
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                'uppercase',
                Rule::unique('rate_types', 'code')->whereNull('deleted_at')
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'code.required' => 'El código es obligatorio',
            'code.unique' => 'Este código ya está en uso',
            'code.uppercase' => 'El código debe estar en mayúsculas',
        ];
    }
}
