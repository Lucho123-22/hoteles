<?php

namespace App\Http\Resources\CashRegister;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CashRegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $session = $this->currentSession;

        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'is_active' => $this->is_active,

            'is_occupied' => !is_null($this->current_session_id),

            'occupied_by' => $session ? [
                'id'   => $session->openedBy->id,
                'name' => $session->openedBy->name,
            ] : null,

            'sub_branch' => [
                'id'   => $this->subBranch->id,
                'name' => $this->subBranch->name,
            ],

            'created_at' => $this->created_at
                ? Carbon::parse($this->created_at)
                    ->format('d/m/Y h:i:s A')
                : null,
        ];
    }
}