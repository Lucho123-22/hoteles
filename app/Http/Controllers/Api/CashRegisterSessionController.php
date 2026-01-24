<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CashRegisterSession\CashRegisterSessionResource;
use App\Models\CashRegisterSession;
use Illuminate\Http\Request;

class CashRegisterSessionController extends Controller{
    public function byCashRegister($cashRegisterId){
        $sessions = CashRegisterSession::query()
            ->where('cash_register_id', $cashRegisterId)
            ->with(['openedBy', 'closedBy'])
            ->orderByDesc('opened_at')
            ->get();

        return CashRegisterSessionResource::collection($sessions);
    }

}
