<?php

namespace App\Http\Controllers\Web\CashSession;

use App\Http\Controllers\Controller;
use App\Http\Resources\CashRegister\CashRegisterResource;
use App\Models\CashRegister;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CashSession extends Controller{
    public function view(string $id){
        Gate::authorize('viewAny', CashRegister::class);
        $cashRegister = CashRegister::with([
                'subBranch',
                'currentSession.openedBy'
            ])
            ->findOrFail($id);
        return Inertia::render('panel/CashSession/indexCashSession', [
            'cashRegister' => new CashRegisterResource($cashRegister),
        ]);
    }
}
