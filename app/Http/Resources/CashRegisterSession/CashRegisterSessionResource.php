<?php

namespace App\Http\Resources\CashRegisterSession;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashRegisterSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'cash_register_id'      => $this->cash_register_id,
            'status'                => $this->status,
            'opening_amount'        => $this->opening_amount,
            'system_total_amount'   => $this->system_total_amount,
            'counted_total_amount'  => $this->counted_total_amount,
            'difference_amount'     => $this->difference_amount,
            'opened_at'             => $this->opened_at,
            'closed_at'             => $this->closed_at,

            'opened_by' => [
                'id'   => $this->openedBy?->id,
                'name' => $this->openedBy?->name,
            ],

            'closed_by' => $this->closedBy?->id ? [
                'id'   => $this->closedBy->id,
                'name' => $this->closedBy->name,
            ] : null,
        ];
    }
}
