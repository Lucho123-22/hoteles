<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datos básicos
            'room_id' => 'required|uuid|exists:rooms,id',
            'customers_id' => 'required|uuid|exists:customers,id',
            'rate_type_id' => 'required|uuid|exists:rate_types,id',
            'currency_id' => 'required|uuid|exists:currencies,id',
            
            // Tiempo y tarifa
            'total_hours' => 'required|integer|min:1|max:720', // Máximo 30 días
            'rate_per_hour' => 'required|numeric|min:0',
            
            // Comprobante
            'voucher_type' => 'required|in:ticket,boleta,factura',
            
            // Pagos
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|uuid|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.cash_register_id' => 'required|uuid|exists:cash_registers,id',
            'payments.*.operation_number' => 'nullable|string|max:100',
            
            // Productos opcionales
            'consumptions' => 'nullable|array',
            'consumptions.*.product_id' => 'required|uuid|exists:products,id',
            'consumptions.*.quantity' => 'required|numeric|min:0.01',
            'consumptions.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'La habitación es obligatoria',
            'room_id.exists' => 'La habitación seleccionada no existe',
            'customers_id.required' => 'El cliente es obligatorio',
            'customers_id.exists' => 'El cliente seleccionado no existe',
            'rate_type_id.required' => 'El tipo de tarifa es obligatorio',
            'currency_id.required' => 'La moneda es obligatoria',
            'total_hours.required' => 'El total de horas o días es obligatorio',
            'total_hours.min' => 'Debe contratar al menos 1 unidad de tiempo',
            'rate_per_hour.required' => 'La tarifa base es obligatoria',
            'voucher_type.required' => 'El tipo de comprobante es obligatorio',
            'voucher_type.in' => 'El tipo de comprobante debe ser: ticket, boleta o factura',
            'payments.required' => 'Debe registrar al menos un pago',
            'payments.*.amount.min' => 'El monto del pago debe ser mayor a 0',
            'payments.*.cash_register_id.required' => 'La caja registradora es obligatoria',
            'consumptions.*.product_id.exists' => 'El producto seleccionado no existe',
        ];
    }

    /**
     * Validación adicional (solo chequea estructura, no montos)
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->has('payments')) {
                return;
            }

            // Solo aseguramos que exista al menos un pago positivo
            $totalPaid = collect($this->payments)->sum('amount');
            if ($totalPaid <= 0) {
                $validator->errors()->add(
                    'payments',
                    'El monto total del pago debe ser mayor a 0.'
                );
            }
        });
    }
}
