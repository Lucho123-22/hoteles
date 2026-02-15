<?php

namespace App\Http\Requests\RateType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRateTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rateTypeId = $this->route('rate_type');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'uppercase',
                Rule::unique('rate_types', 'code')
                    ->ignore($rateTypeId)
                    ->whereNull('deleted_at')
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
