<?php

namespace App\Http\Requests\Movement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Preparar los datos antes de la validación
     */
    protected function prepareForValidation()
    {
        // Agregar automáticamente el sub_branch_id del usuario autenticado
        $this->merge([
            'sub_branch_id' => Auth::user()->sub_branch_id,
        ]);
    }

    public function rules(): array
    {
        $movementId = $this->route('movement')->id;

        return [
            'code' => [
                'required',
                'string',
                Rule::unique('movements', 'code')->ignore($movementId)
            ],
            'date' => ['required', 'date'],
            'provider_id'   => ['required', 'exists:providers,id'],
            'sub_branch_id' => ['required', 'exists:sub_branches,id'],
            'payment_type'  => ['required', 'in:credito,contado'],
            'credit_date'   => ['required_if:payment_type,credito', 'nullable', 'date'],
            'includes_igv'  => ['required', 'boolean'],
            'voucher_type'  => ['required', 'in:factura,boleta,guia'], // CORREGIDO: era 'otros', ahora 'guia'
            'movement_type' => ['required', 'in:ingreso,egreso'], // NUEVO: agregado según tu frontend
            'created_by'    => ['nullable', 'exists:users,id'],
            'updated_by'    => ['nullable', 'exists:users,id'],
            'deleted_by'    => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'          => 'El código del movimiento es obligatorio.',
            'code.unique'            => 'Este código ya existe.',
            'date.required'          => 'La fecha es obligatoria.',
            'date.date'              => 'Debe ser una fecha válida.',
            'provider_id.required'   => 'El proveedor es obligatorio.',
            'provider_id.exists'     => 'El proveedor seleccionado no existe.',
            'sub_branch_id.required' => 'La sub-sucursal es obligatoria.',
            'sub_branch_id.exists'   => 'La sub-sucursal seleccionada no existe.',
            'payment_type.required'  => 'El tipo de pago es obligatorio.',
            'payment_type.in'        => 'El tipo de pago debe ser "credito" o "contado".',
            'credit_date.required_if'=> 'La fecha de crédito es obligatoria cuando el pago es a crédito.',
            'credit_date.date'       => 'La fecha de crédito debe ser una fecha válida.',
            'includes_igv.required'  => 'Debe indicar si incluye IGV.',
            'includes_igv.boolean'   => 'El valor de IGV debe ser verdadero o falso.',
            'voucher_type.required'  => 'El tipo de comprobante es obligatorio.',
            'voucher_type.in'        => 'El tipo de comprobante debe ser "factura", "boleta" o "guia".',
            'movement_type.required' => 'El tipo de movimiento es obligatorio.',
            'movement_type.in'       => 'El tipo de movimiento debe ser "ingreso" o "egreso".',
            'created_by.exists'      => 'El usuario creador no existe.',
            'updated_by.exists'      => 'El usuario que actualiza no existe.',
            'deleted_by.exists'      => 'El usuario que elimina no existe.',
        ];
    }
}