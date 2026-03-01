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
            'pricing_range_id' => 'required|uuid|exists:pricing_ranges,id', // 🔥 NUEVO
            'currency_id' => 'required|uuid|exists:currencies,id',
            
            // Tiempo y tarifa
            'quantity' => 'required|integer|min:1|max:365',
            'rate_per_hour' => 'required|numeric|min:0',
            'rate_per_unit' => 'nullable|numeric|min:0',
            
            // Comprobante
            'voucher_type' => 'required|in:ticket,boleta,factura',
            
            // Pagos
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|uuid|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.cash_register_id' => 'nullable|uuid|exists:cash_registers,id',
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
            'rate_type_id.exists' => 'El tipo de tarifa seleccionado no existe',
            
            'pricing_range_id.required' => 'El rango de precio es obligatorio',
            'pricing_range_id.exists' => 'El rango de precio seleccionado no existe',
            
            'currency_id.required' => 'La moneda es obligatoria',
            'currency_id.exists' => 'La moneda seleccionada no existe',
            
            'quantity.required' => 'La cantidad es obligatoria',
            'quantity.integer' => 'La cantidad debe ser un número entero',
            'quantity.min' => 'Debe contratar al menos 1 unidad',
            'quantity.max' => 'No puede contratar más de 365 unidades',
            
            'rate_per_hour.required' => 'La tarifa base es obligatoria',
            'rate_per_hour.numeric' => 'La tarifa base debe ser un número',
            'rate_per_hour.min' => 'La tarifa base debe ser mayor o igual a 0',
            
            'voucher_type.required' => 'El tipo de comprobante es obligatorio',
            'voucher_type.in' => 'El tipo de comprobante debe ser: ticket, boleta o factura',
            
            'payments.required' => 'Debe registrar al menos un pago',
            'payments.array' => 'Los pagos deben ser un arreglo',
            'payments.min' => 'Debe registrar al menos un pago',
            'payments.*.payment_method_id.required' => 'El método de pago es obligatorio',
            'payments.*.payment_method_id.exists' => 'El método de pago seleccionado no existe',
            'payments.*.amount.required' => 'El monto del pago es obligatorio',
            'payments.*.amount.numeric' => 'El monto del pago debe ser un número',
            'payments.*.amount.min' => 'El monto del pago debe ser mayor a 0',
            'payments.*.cash_register_id.exists' => 'La caja registradora seleccionada no existe',
            
            'consumptions.array' => 'Los consumos deben ser un arreglo',
            'consumptions.*.product_id.required' => 'El producto es obligatorio',
            'consumptions.*.product_id.exists' => 'El producto seleccionado no existe',
            'consumptions.*.quantity.required' => 'La cantidad del producto es obligatoria',
            'consumptions.*.quantity.numeric' => 'La cantidad debe ser un número',
            'consumptions.*.quantity.min' => 'La cantidad debe ser mayor a 0',
            'consumptions.*.unit_price.required' => 'El precio unitario es obligatorio',
            'consumptions.*.unit_price.numeric' => 'El precio unitario debe ser un número',
            'consumptions.*.unit_price.min' => 'El precio unitario debe ser mayor o igual a 0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->has('payments')) {
                return;
            }

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