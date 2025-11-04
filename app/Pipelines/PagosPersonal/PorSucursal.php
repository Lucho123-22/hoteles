<?php

namespace App\Pipelines\PagosPersonal;

use Closure;

class PorSucursal
{
    public function handle($query, Closure $next)
    {
        $sucursalFiltro = request('sub_branch_id');
        
        // Si NO se envía sucursal, retornar query vacía
        if (!$sucursalFiltro) {
            $query->whereRaw('1 = 0'); // Esto garantiza 0 resultados
        } else {
            $query->where('sub_branch_id', $sucursalFiltro);
        }
        
        return $next($query);
    }
}