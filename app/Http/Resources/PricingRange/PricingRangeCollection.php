<?php

namespace App\Http\Resources\PricingRange;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PricingRangeCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => PricingRangeResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
                'price_stats' => [
                    'min' => $this->collection->min('price'),
                    'max' => $this->collection->max('price'),
                    'avg' => round($this->collection->avg('price'), 2),
                ],
            ],
        ];
    }
}
