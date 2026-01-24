<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckCashRegisterOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 🔑 Verificar si el usuario tiene una sesión de caja abierta
        $hasOpenCashRegister =
            CashRegisterSession::userHasOpenSession($user->id);

        // Si está en la vista de apertura y YA tiene caja → redirigir
        if ($request->routeIs('aperturar.view') && $hasOpenCashRegister) {
            return redirect()->route('online.view')
                ->with('info', 'Ya tienes una caja aperturada.');
        }

        // Si la ruta requiere caja abierta y NO tiene → forzar apertura
        if (!$hasOpenCashRegister && $this->requiresCashRegister($request)) {
            return redirect()->route('aperturar.view')
                ->with('error', 'Debes aperturar una caja antes de acceder a esta sección.');
        }

        return $next($request);
    }

    /**
     * Rutas que requieren caja abierta
     */
    protected function requiresCashRegister(Request $request): bool
    {
        return $request->routeIs([
            'online.view',
            'cuarto.view',
            'cloasebox.view',
        ]);
    }
}
