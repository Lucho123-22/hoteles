<?php

namespace App\Http\Resources\RateType;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RateTypeCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => RateTypeResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
