<?php

namespace App\Http\Requests\RoomType;

use App\Models\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomTypeId = $this->route('room_type');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'uppercase',
                Rule::unique('room_types', 'code')
                    ->ignore($roomTypeId)
                    ->whereNull('deleted_at')
            ],
            'description' => ['nullable', 'string'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'category' => [
                'nullable',
                'string',
                Rule::in(RoomType::getCategories())
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'capacity.required' => 'La capacidad es obligatoria',
            'capacity.min' => 'La capacidad debe ser al menos 1',
            'category.in' => 'La categoría seleccionada no es válida',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Validar que max_capacity >= capacity si ambos están presentes
        if ($this->has('max_capacity') && $this->has('capacity')) {
            $this->merge([
                'max_capacity' => max($this->max_capacity, $this->capacity)
            ]);
        }
    }
}
