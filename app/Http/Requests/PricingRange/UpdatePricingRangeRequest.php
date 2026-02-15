<?php

namespace App\Http\Requests\PricingRange;

use App\Models\PricingRange;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sub_branch_id' => ['sometimes', 'required', 'uuid', 'exists:sub_branches,id'],
            'room_type_id' => ['sometimes', 'required', 'uuid', 'exists:room_types,id'],
            'rate_type_id' => ['sometimes', 'required', 'uuid', 'exists:rate_types,id'],
            
            'time_from_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:43200',
            ],
            'time_to_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:43200',
                'gt:time_from_minutes',
            ],
            
            'price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:9999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            
            'effective_from' => ['sometimes', 'required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sub_branch_id.exists' => 'La sucursal seleccionada no existe',
            'room_type_id.exists' => 'El tipo de habitación seleccionado no existe',
            'rate_type_id.exists' => 'El tipo de tarifa seleccionado no existe',
            
            'time_from_minutes.integer' => 'El tiempo inicial debe ser un número entero',
            'time_from_minutes.min' => 'El tiempo inicial debe ser mayor o igual a 0',
            'time_from_minutes.max' => 'El tiempo inicial no puede exceder 30 días',
            
            'time_to_minutes.integer' => 'El tiempo final debe ser un número entero',
            'time_to_minutes.gt' => 'El tiempo final debe ser mayor al tiempo inicial',
            'time_to_minutes.max' => 'El tiempo final no puede exceder 30 días',
            
            'price.numeric' => 'El precio debe ser un valor numérico',
            'price.min' => 'El precio debe ser mayor o igual a 0',
            'price.max' => 'El precio excede el límite permitido',
            'price.regex' => 'El precio debe tener máximo 2 decimales',
            
            'effective_from.date' => 'La fecha de inicio debe ser una fecha válida',
            'effective_to.date' => 'La fecha de fin debe ser una fecha válida',
            'effective_to.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active') && !is_bool($this->is_active)) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateTimeConsistency($validator);
            $this->validateOverlap($validator);
            $this->validateBusinessRules($validator);
        });
    }

    protected function validateTimeConsistency($validator): void
    {
        $pricingRange = $this->route('pricing_range');
        
        $timeFrom = $this->input('time_from_minutes', $pricingRange->time_from_minutes);
        $timeTo = $this->input('time_to_minutes', $pricingRange->time_to_minutes);

        if (($timeFrom !== null && $timeTo === null) || ($timeFrom === null && $timeTo !== null)) {
            $validator->errors()->add(
                'time_range',
                'Debe definir ambos tiempos (desde y hasta) o dejar ambos vacíos'
            );
        }

        if ($timeFrom !== null && $timeTo !== null) {
            $duration = $timeTo - $timeFrom;
            
            if ($duration < 15) {
                $validator->errors()->add(
                    'time_range',
                    'El rango de tiempo debe ser de al menos 15 minutos'
                );
            }
        }
    }

    protected function validateOverlap($validator): void
    {
        $pricingRange = $this->route('pricing_range');

        $hasOverlap = PricingRange::hasOverlap(
            subBranchId: $this->input('sub_branch_id', $pricingRange->sub_branch_id),
            roomTypeId: $this->input('room_type_id', $pricingRange->room_type_id),
            rateTypeId: $this->input('rate_type_id', $pricingRange->rate_type_id),
            timeFrom: $this->input('time_from_minutes', $pricingRange->time_from_minutes),
            timeTo: $this->input('time_to_minutes', $pricingRange->time_to_minutes),
            effectiveFrom: $this->input('effective_from', $pricingRange->effective_from),
            effectiveTo: $this->input('effective_to', $pricingRange->effective_to),
            excludeId: $pricingRange->id
        );

        if ($hasOverlap) {
            $validator->errors()->add(
                'overlap',
                'Ya existe una configuración de precio que se solapa con los rangos especificados'
            );
        }
    }

    protected function validateBusinessRules($validator): void
    {
        $pricingRange = $this->route('pricing_range');
        $isActive = $this->input('is_active', $pricingRange->is_active);
        $price = $this->input('price', $pricingRange->price);

        if ($isActive && $price == 0) {
            $validator->errors()->add(
                'price',
                'El precio debe ser mayor a 0 para rangos activos'
            );
        }
    }
}