<?php

namespace App\Http\Requests\Cash;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate se valida en el controlador
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Debe indicar la cantidad de cajas a crear',
            'quantity.integer'  => 'La cantidad debe ser un número entero',
            'quantity.min'      => 'Debe crear al menos una caja',
        ];
    }
}
