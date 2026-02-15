<?php

namespace App\Http\Resources\RateType;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RateTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'display_name' => $this->getDisplayName(),
            'icon' => $this->getIcon(),
            'requires_time_range' => $this->requiresTimeRange(),
            
            // Metadatos
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relaciones opcionales
            'pricing_ranges_count' => $this->whenCounted('pricingRanges'),
        ];
    }
}
