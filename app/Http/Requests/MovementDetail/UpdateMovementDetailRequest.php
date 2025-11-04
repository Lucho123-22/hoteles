<?php
namespace App\Http\Requests\MovementDetail;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Movement;

class UpdateMovementDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'    => 'sometimes|uuid|exists:products,id',
            'unit_price'    => 'sometimes|numeric|min:0',
            'boxes'         => 'sometimes|integer|min:0',
            'units_per_box' => 'sometimes|integer|min:1',
            'fractions'     => 'sometimes|integer|min:0',
            'quantity_type' => 'sometimes|in:packages,fractions,both',
            'expiry_date'   => 'nullable|date|after:today',
            'total_price'   => 'sometimes|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('quantity_type')) {
                if ($this->quantity_type === 'packages' && $this->input('fractions', 0) > 0) {
                    $validator->errors()->add('fractions', 'No puede haber fracciones cuando el tipo es solo paquetes.');
                }
                if ($this->quantity_type === 'fractions' && $this->input('boxes', 0) > 0) {
                    $validator->errors()->add('boxes', 'No puede haber paquetes cuando el tipo es solo fracciones.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'product_id.exists'    => 'El producto seleccionado no existe.',
            'unit_price.numeric'   => 'El precio unitario debe ser numérico.',
            'boxes.integer'        => 'La cantidad de cajas debe ser un número entero.',
            'units_per_box.min'    => 'Las unidades por caja deben ser al menos 1.',
            'fractions.integer'    => 'Las fracciones deben ser un número entero.',
            'quantity_type.in'     => 'El tipo de cantidad debe ser: paquetes, fracciones o ambas.',
            'total_price.numeric'  => 'El precio total debe ser numérico.',
        ];
    }
}