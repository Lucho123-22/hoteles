<?php
namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => [
                'required',
                'string',
                'min:2',
                'max:100',
                'unique:products,name',
            ],
            'category_id'     => ['required', 'exists:product_categories,id'],
            'is_active'       => ['required', 'boolean'],
            'purchase_price'  => ['required', 'numeric', 'min:0'],
            'sale_price'      => ['required', 'numeric', 'min:0'],
            'unit_type'       => ['required', 'in:piece,bottle,pack,kg,liter'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'is_fractionable' => ['required', 'boolean'],
            'fraction_units'  => [
                'nullable',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    if ($this->input('is_fractionable') && !$value) {
                        $fail('Fraction units are required when product is fractionable.');
                    }
                },
            ],
            'min_stock'       => ['required', 'integer', 'min:0'],
            'max_stock'       => ['required', 'integer', 'min:0', 'gte:min_stock'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'El nombre es obligatorio.',
            'name.unique'     => 'Este producto ya existe.',
            'name.min'        => 'El nombre debe tener al menos 2 caracteres.',
            'name.max'        => 'El nombre no debe exceder los 100 caracteres.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists'   => 'La categoría seleccionada no existe.',
            'is_active.required'   => 'El estado es obligatorio.',
            'is_active.boolean'    => 'El estado debe ser verdadero o falso.',
            'purchase_price.required' => 'El precio de compra es obligatorio.',
            'purchase_price.numeric'  => 'El precio de compra debe ser numérico.',
            'purchase_price.min'      => 'El precio de compra no puede ser negativo.',
            'sale_price.required'     => 'El precio de venta es obligatorio.',
            'sale_price.numeric'      => 'El precio de venta debe ser numérico.',
            'sale_price.min'          => 'El precio de venta no puede ser negativo.',
            'unit_type.required'      => 'El tipo de unidad es obligatorio.',
            'unit_type.in'            => 'El tipo de unidad seleccionado no es válido.',
            'description.string'      => 'La descripción debe ser un texto.',
            'description.max'         => 'La descripción no debe exceder los 1000 caracteres.',
            'is_fractionable.required'=> 'Debe indicar si el producto es fraccionable.',
            'fraction_units.min'      => 'Las unidades fraccionadas deben ser al menos 1.',
            'min_stock.required'      => 'El stock mínimo es obligatorio.',
            'max_stock.gte'           => 'El stock máximo debe ser mayor o igual al stock mínimo.',
        ];
    }
}
