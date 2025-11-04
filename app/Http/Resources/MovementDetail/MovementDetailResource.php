<?php
namespace App\Http\Resources\MovementDetail;

use Illuminate\Http\Resources\Json\JsonResource;

class MovementDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $tipo = match ($this->quantity_type) {
            'packages' => 'Paquete',
            'fractions' => 'Fracción',
            'packages_fractions', 'both' => 'Paquete - Fracción',
            default => '-',
        };

        $boxes = (int) ($this->boxes ?? 0);
        $fractions = (int) ($this->fractions ?? 0);
        
        $cantidades = match ($this->quantity_type) {
            'packages' => (string)$boxes,
            'fractions' => (string)$fractions,
            'packages_fractions', 'both' => "{$boxes} - {$fractions}",
            default => '0',
        };

        return [
            'id' => $this->id,
            'tipo' => $tipo,
            'cantidades' => $cantidades,
            'producto' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'nombre' => $this->product->name,
                    'codigo' => $this->product->code ?? null,
                    'es_fraccionable' => (bool) $this->product->is_fractionable,
                ];
            }),
            'fecha_vencimiento' => $this->expiry_date
                ? date('d/m/Y', strtotime($this->expiry_date))
                : null,
            'precio_unitario' => number_format((float)($this->unit_price ?? 0), 2),
            'precio_total' => number_format((float)($this->total_price ?? 0), 2),
        ];
    }
}