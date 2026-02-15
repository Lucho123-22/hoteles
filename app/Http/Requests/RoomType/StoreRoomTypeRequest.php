<?php

namespace App\Http\Requests\RoomType;

use App\Models\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'uppercase',
                Rule::unique('room_types', 'code')->whereNull('deleted_at')
            ],
            'description' => ['nullable', 'string'],
            'capacity' => ['required', 'integer', 'min:1'],
            'max_capacity' => ['nullable', 'integer', 'min:1', 'gte:capacity'],
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
            'max_capacity.gte' => 'La capacidad máxima debe ser mayor o igual a la capacidad estándar',
            'category.in' => 'La categoría seleccionada no es válida',
        ];
    }
}
