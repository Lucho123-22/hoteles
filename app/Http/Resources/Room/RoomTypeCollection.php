<?php

namespace App\Http\Resources\Room;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RoomTypeCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => RoomTypeResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
                'categories' => $this->collection->pluck('category')->unique()->values(),
            ],
        ];
    }
}
