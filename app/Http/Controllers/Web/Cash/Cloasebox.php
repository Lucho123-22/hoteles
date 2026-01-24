<?php

namespace App\Http\Controllers\Web\Cash;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class Cloasebox extends Controller{
    public function view(){
        return Inertia::render('panel/CloaseBox/indexCloaseBox');
    }
}
