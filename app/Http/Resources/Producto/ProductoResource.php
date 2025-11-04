<?php

namespace App\Http\Resources\Producto;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'codigo' => $this->code,
            'nombre' => $this->name,
            'descripcion' => $this->description,
            'precio_compra' => $this->purchase_price,
            'precio_venta' => $this->sale_price,
            'unidad' => $this->unit_type,
            'categoria_id' => $this->category_id,
            'Categoria_nombre' => $this->category?->name ?? 'Sin categoría',
            'estado' => $this->is_active,
            'is_fractionable' => $this->is_fractionable,
            'fraction_units' => $this->is_fractionable ? $this->fraction_units : 0,
            'min_stock' => $this->subBranchProducts->first()?->min_stock,
            'max_stock' => $this->subBranchProducts->first()?->max_stock,

            'creacion' => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'actualizacion' => Carbon::parse($this->updated_at)->format('d-m-Y H:i:s A'),
        ];
    }
}
