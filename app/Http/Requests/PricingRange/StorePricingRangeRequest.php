<?php

namespace App\Http\Requests\PricingRange;

use App\Models\PricingRange;
use Illuminate\Foundation\Http\FormRequest;

class StorePricingRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sub_branch_id' => ['required', 'uuid', 'exists:sub_branches,id'],
            'room_type_id' => ['required', 'uuid', 'exists:room_types,id'],
            'rate_type_id' => ['required', 'uuid', 'exists:rate_types,id'],
            
            // Tiempos - siempre opcionales, el usuario decide
            'time_from_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:43200', // máximo 30 días en minutos
            ],
            'time_to_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:43200',
                'gt:time_from_minutes',
            ],
            
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            
            'effective_from' => ['required', 'date'],
            'effective_to' => [
                'nullable',
                'date',
                'after:effective_from',
            ],
            
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sub_branch_id.required' => 'La sucursal es obligatoria',
            'sub_branch_id.exists' => 'La sucursal seleccionada no existe',
            
            'room_type_id.required' => 'El tipo de habitación es obligatorio',
            'room_type_id.exists' => 'El tipo de habitación seleccionado no existe',
            
            'rate_type_id.required' => 'El tipo de tarifa es obligatorio',
            'rate_type_id.exists' => 'El tipo de tarifa seleccionado no existe',
            
            'time_from_minutes.integer' => 'El tiempo inicial debe ser un número entero',
            'time_from_minutes.min' => 'El tiempo inicial debe ser mayor o igual a 0',
            'time_from_minutes.max' => 'El tiempo inicial no puede exceder 30 días',
            
            'time_to_minutes.integer' => 'El tiempo final debe ser un número entero',
            'time_to_minutes.gt' => 'El tiempo final debe ser mayor al tiempo inicial',
            'time_to_minutes.max' => 'El tiempo final no puede exceder 30 días',
            
            'price.required' => 'El precio es obligatorio',
            'price.numeric' => 'El precio debe ser un valor numérico',
            'price.min' => 'El precio debe ser mayor o igual a 0',
            'price.max' => 'El precio excede el límite permitido',
            'price.regex' => 'El precio debe tener máximo 2 decimales',
            
            'effective_from.required' => 'La fecha de inicio es obligatoria',
            'effective_from.date' => 'La fecha de inicio debe ser una fecha válida',
            
            'effective_to.date' => 'La fecha de fin debe ser una fecha válida',
            'effective_to.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
            
            'is_active.boolean' => 'El estado debe ser verdadero o falso',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalizar valores booleanos
        if ($this->has('is_active') && !is_bool($this->is_active)) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Convertir tiempos vacíos a null
        if (!$this->filled('time_from_minutes')) {
            $this->merge(['time_from_minutes' => null]);
        }
        
        if (!$this->filled('time_to_minutes')) {
            $this->merge(['time_to_minutes' => null]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Solo validar que ambos tiempos estén presentes o ambos ausentes
            $this->validateTimeConsistency($validator);
            
            // Validar solapamiento
            $this->validateOverlap($validator);
            
            // Validar reglas de negocio básicas
            $this->validateBusinessRules($validator);
        });
    }

    /**
     * Validar consistencia de tiempos
     */
    protected function validateTimeConsistency($validator): void
    {
        $timeFrom = $this->time_from_minutes;
        $timeTo = $this->time_to_minutes;

        // Si uno está definido, el otro también debe estarlo
        if (($timeFrom !== null && $timeTo === null) || ($timeFrom === null && $timeTo !== null)) {
            $validator->errors()->add(
                'time_range',
                'Debe definir ambos tiempos (desde y hasta) o dejar ambos vacíos'
            );
        }

        // Si ambos están definidos, validar duración mínima (opcional)
        if ($timeFrom !== null && $timeTo !== null) {
            $duration = $timeTo - $timeFrom;
            
            if ($duration < 15) { // Mínimo 15 minutos
                $validator->errors()->add(
                    'time_range',
                    'El rango de tiempo debe ser de al menos 15 minutos'
                );
            }
        }
    }

    /**
     * Validar solapamiento de rangos
     */
    protected function validateOverlap($validator): void
    {
        $hasOverlap = PricingRange::hasOverlap(
            subBranchId: $this->sub_branch_id,
            roomTypeId: $this->room_type_id,
            rateTypeId: $this->rate_type_id,
            timeFrom: $this->time_from_minutes,
            timeTo: $this->time_to_minutes,
            effectiveFrom: $this->effective_from,
            effectiveTo: $this->effective_to
        );

        if ($hasOverlap) {
            $validator->errors()->add(
                'overlap',
                'Ya existe una configuración de precio que se solapa con los rangos especificados (tiempo y/o fechas)'
            );
        }
    }

    /**
     * Validar reglas de negocio adicionales
     */
    protected function validateBusinessRules($validator): void
    {
        // Validar que el precio no sea 0 si está activo
        if ($this->boolean('is_active', true) && $this->price == 0) {
            $validator->errors()->add(
                'price',
                'El precio debe ser mayor a 0 para rangos activos'
            );
        }
    }
}