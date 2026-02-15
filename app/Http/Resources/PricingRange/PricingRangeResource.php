<?php

namespace App\Http\Resources\PricingRange;

use App\Http\Resources\RateType\RateTypeResource;
use App\Http\Resources\Room\RoomTypeResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingRangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sub_branch_id' => $this->sub_branch_id,
            'room_type_id' => $this->room_type_id,
            'rate_type_id' => $this->rate_type_id,
            
            // Rangos de tiempo
            'time_from_minutes' => $this->time_from_minutes,
            'time_to_minutes' => $this->time_to_minutes,
            'formatted_time_range' => $this->getFormattedTimeRange(),
            'duration_hours' => $this->getDurationInHours(),
            
            // Precio
            'price' => (float) $this->price,
            'price_per_hour' => $this->when(
                $this->isHourlyRate(),
                fn() => (float) $this->getPricePerHour()
            ),
            
            // Vigencia
            'effective_from' => Carbon::parse($this->effective_from)->format('d-m-Y'),
            'effective_to' => Carbon::parse($this->effective_to)->format('d-m-Y'),

            'is_effective' => $this->isEffective(),
            
            // Estado
            'is_active' => $this->is_active,
            
            // Flags útiles
            'is_hourly_rate' => $this->isHourlyRate(),
            'is_daily_rate' => $this->isDailyRate(),
            'is_nightly_rate' => $this->isNightlyRate(),
            
            // Relaciones
            'room_type' => $this->when(
                $this->relationLoaded('roomType'),
                fn() => new RoomTypeResource($this->roomType)
            ),
            'rate_type' => $this->when(
                $this->relationLoaded('rateType'),
                fn() => new RateTypeResource($this->rateType)
            ),
            
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'updated_at' => Carbon::parse($this->updated_at)->format('d-m-Y H:i:s A'),
        ];
    }
}
