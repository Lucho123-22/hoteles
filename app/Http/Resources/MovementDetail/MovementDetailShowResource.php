<?php
namespace App\Http\Resources\MovementDetail;

use Illuminate\Http\Resources\Json\JsonResource;

class MovementDetailShowResource extends JsonResource{
    public function toArray($request): array{
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'unit_price' => $this->unit_price,
            'boxes' => $this->boxes,
            'units_per_box' => $this->units_per_box,
            'fractions' => $this->fractions,
            'quantity_type' => $this->quantity_type,
            'expiry_date' => $this->expiry_date,
            'total_price' => $this->total_price,];
    }
}