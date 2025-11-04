<?php

namespace App\Http\Requests\PagoPersonal;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePagoPersonalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'sub_branch_id' => 'required|exists:sub_branches,id',
            'monto' => 'required|numeric|min:0|max:999999.99',
            'fecha_pago' => 'required|date',
            'periodo' => 'required|string|max:255',
            'tipo_pago' => 'required|in:salario,adelanto,bonificacion,comision,otro',
            'metodo_pago' => 'required|in:efectivo,transferencia,cheque',
            'concepto' => 'nullable|string|max:1000',
            'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'estado' => 'required|in:pendiente,pagado,anulado'
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El empleado es obligatorio',
            'user_id.exists' => 'El empleado seleccionado no existe',
            'sub_branch_id.required' => 'La sucursal es obligatoria',
            'sub_branch_id.exists' => 'La sucursal seleccionada no existe',
            'monto.required' => 'El monto es obligatorio',
            'monto.numeric' => 'El monto debe ser numérico',
            'monto.min' => 'El monto debe ser mayor a 0',
            'fecha_pago.required' => 'La fecha de pago es obligatoria',
            'fecha_pago.date' => 'La fecha de pago no es válida',
            'periodo.required' => 'El periodo es obligatorio',
            'tipo_pago.required' => 'El tipo de pago es obligatorio',
            'tipo_pago.in' => 'El tipo de pago no es válido',
            'metodo_pago.required' => 'El método de pago es obligatorio',
            'metodo_pago.in' => 'El método de pago no es válido',
            'comprobante.mimes' => 'El comprobante debe ser un archivo PDF o imagen',
            'comprobante.max' => 'El comprobante no debe pesar más de 2MB',
            'estado.required' => 'El estado es obligatorio',
            'estado.in' => 'El estado no es válido'
        ];
    }
}