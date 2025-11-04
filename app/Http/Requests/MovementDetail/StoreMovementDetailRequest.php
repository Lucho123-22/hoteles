<?php
namespace App\Http\Requests\MovementDetail;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Movement;

class StoreMovementDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movement_id'   => 'required|uuid|exists:movements,id',
            'product_id'    => 'required|uuid|exists:products,id',
            'unit_price'    => 'required|numeric|min:0',
            'boxes'         => 'required|integer|min:0',
            'units_per_box' => 'required|integer|min:1',
            'fractions'     => 'required|integer|min:0',
            'quantity_type' => 'required|in:packages,fractions,both',
            'expiry_date'   => 'nullable|date|after:today',
            'total_price'   => 'required|numeric|min:0', // ✅ AGREGAR ESTA LÍNEA
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->movement_id) {
                $movement = Movement::find($this->movement_id);
                if (!$movement) {
                    $validator->errors()->add('movement_id', 'El movimiento no existe.');
                }
                if ($movement && $movement->movement_type === 'egreso') {
                    // Validaciones para egresos si las necesitas
                }
            }

            // ✅ Validar que la combinación de campos sea coherente
            if ($this->quantity_type === 'packages' && $this->fractions > 0) {
                $validator->errors()->add('fractions', 'No puede haber fracciones cuando el tipo es solo paquetes.');
            }
            if ($this->quantity_type === 'fractions' && $this->boxes > 0) {
                $validator->errors()->add('boxes', 'No puede haber paquetes cuando el tipo es solo fracciones.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'movement_id.required' => 'El movimiento es obligatorio.',
            'movement_id.exists'   => 'El movimiento seleccionado no existe.',
            'product_id.required'  => 'El producto es obligatorio.',
            'product_id.exists'    => 'El producto seleccionado no existe.',
            'unit_price.required'  => 'El precio unitario es obligatorio.',
            'boxes.required'       => 'La cantidad de cajas es obligatoria.',
            'units_per_box.required' => 'Las unidades por caja son obligatorias.',
            'fractions.required'   => 'Las fracciones son obligatorias.',
            'quantity_type.required' => 'El tipo de cantidad es obligatorio.',
            'quantity_type.in'     => 'El tipo de cantidad debe ser: paquetes, fracciones o ambas.',
            'total_price.required' => 'El precio total es obligatorio.', // ✅ AGREGAR ESTE MENSAJE
            'total_price.min'      => 'El precio total debe ser mayor o igual a 0.', // ✅ OPCIONAL
        ];
    }
}