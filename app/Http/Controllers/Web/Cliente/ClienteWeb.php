<?php

namespace App\Http\Controllers\Web\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class ClienteWeb extends Controller{
    public function view(): Response{
        //Gate::authorize('viewAny', Cliente::class);
        return Inertia::render('panel/Cliente/indexCliente');
    }
}
