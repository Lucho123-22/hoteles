<?php

namespace App\Http\Resources\Room;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'max_capacity' => $this->max_capacity,
            'category' => $this->category,
            'is_active' => $this->is_active,
            
            // Información adicional
            'available_rooms_count' => $this->when(
                $request->boolean('with_available_rooms'),
                fn() => $this->getAvailableRoomsCount()
            ),
            
            'has_available_rooms' => $this->when(
                $request->boolean('with_available_rooms'),
                fn() => $this->hasAvailableRooms()
            ),
            
            'price_range' => $this->when(
                $request->filled('sub_branch_id') && $request->boolean('with_prices'),
                fn() => $this->getPriceRange($request->sub_branch_id, $request->rate_type_code)
            ),
            
            'cheapest_price' => $this->when(
                $request->filled('sub_branch_id') && $request->boolean('with_prices'),
                fn() => $this->getCheapestPrice($request->sub_branch_id, $request->rate_type_code)?->price
            ),
            
            // Metadatos
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Contadores opcionales
            'rooms_count' => $this->whenCounted('rooms'),
            'pricing_ranges_count' => $this->whenCounted('pricingRanges'),
        ];
    }
}
