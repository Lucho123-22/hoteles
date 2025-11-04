<?php

namespace App\Http\Resources\Kardex;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class KardexValorizadoResource extends JsonResource
{
    public function toArray($request)
    {
        $costoUnitario = $this->costo_unitario ?? 0;
        $cantidadTotal = ($this->cantidadCaja * $this->product?->fraccion ?? 1) + $this->cantidadFraccion;
        $costoMovimiento = $cantidadTotal * $costoUnitario;

        $saldoCantidad = ($this->SParcialCaja * $this->product?->fraccion ?? 1) + $this->SParcialFraccion;
        $saldoValorizado = $saldoCantidad * $costoUnitario;

        return [
            'id'                 => $this->id,
            'fecha'              => Carbon::parse($this->created_at)->format('d-m-Y H:i'),
            'producto'           => $this->product?->name ?? 'N/A',
            'sucursal'           => $this->subBranch?->nombre ?? 'N/A',
            'tipo_movimiento'    => ucfirst($this->movement_type),
            'cantidad_caja'      => $this->cantidadCaja,
            'cantidad_fraccion'  => $this->cantidadFraccion,
            'costo_unitario'     => number_format($costoUnitario, 2),
            'costo_total'        => number_format($costoMovimiento, 2),
            'saldo_caja'         => $this->SParcialCaja,
            'saldo_fraccion'     => $this->SParcialFraccion,
            'saldo_valorizado'   => number_format($saldoValorizado, 2),
            'precio_venta'       => number_format($this->precio_total ?? 0, 2),
            'estado'             => $this->estado,
        ];
    }
}
