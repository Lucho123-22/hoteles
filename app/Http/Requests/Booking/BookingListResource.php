<?php

namespace App\Http\Requests\Booking;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Versión optimizada para listados - Solo datos esenciales
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            
            // Cliente - Solo info básica
            'customer_name' => $this->customer?->name,
            'customer_document' => $this->customer?->document_number,
            'customer_phone' => $this->customer?->phone,
            
            // Habitación - Info resumida
            'room_name' => $this->room?->full_name ?? $this->room?->name,
            'room_number' => $this->room?->room_number,
            'floor_name' => $this->room?->floor?->name,
            'sub_branch_name' => $this->room?->floor?->subBranch?->name,
            
            // Tarifa
            'rate_type_name' => $this->rateType?->name,
            'rate_type_code' => $this->rateType?->code,
            
            // Moneda
            'currency_code' => $this->currency?->code,
            'currency_symbol' => $this->currency?->symbol,
            
            // Fechas
            'check_in' => $this->check_in?->format('Y-m-d H:i:s'),
            'check_in_date' => $this->check_in?->format('d/m/Y'),
            'check_in_time' => $this->check_in?->format('H:i'),
            'check_out' => $this->check_out?->format('Y-m-d H:i:s'),
            'check_out_date' => $this->check_out?->format('d/m/Y'),
            'check_out_time' => $this->check_out?->format('H:i'),
            'total_hours' => $this->total_hours,
            'duration' => $this->duration_in_words,
            
            // Montos - Solo principales
            'rate_per_unit' => number_format($this->rate_per_unit, 2, '.', ''),
            'subtotal' => number_format($this->subtotal, 2, '.', ''),
            'total_amount' => number_format($this->total_amount, 2, '.', ''),
            'paid_amount' => number_format($this->paid_amount, 2, '.', ''),
            'balance' => number_format($this->balance, 2, '.', ''),
            
            // Montos formateados para display
            'total_amount_formatted' => $this->currency?->symbol . ' ' . number_format($this->total_amount, 2),
            'paid_amount_formatted' => $this->currency?->symbol . ' ' . number_format($this->paid_amount, 2),
            'balance_formatted' => $this->currency?->symbol . ' ' . number_format($this->balance, 2),
            
            // Estado
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'status_badge' => $this->getStatusBadge(),
            
            // Flags útiles
            'is_paid' => $this->isPaid(),
            'has_balance' => $this->balance > 0,
            'is_active' => in_array($this->status, ['confirmed', 'checked_in']),
            'can_check_in' => $this->status === 'confirmed',
            'can_check_out' => $this->status === 'checked_in',
            'can_cancel' => in_array($this->status, ['pending', 'confirmed']),
            
            // Contadores
            'payments_count' => $this->payments->count(),
            'consumptions_count' => $this->consumptions->count(),
            'total_consumptions' => number_format($this->consumptions->sum('total_price'), 2, '.', ''),
            
            // Métodos de pago usados (resumido)
            'payment_methods' => $this->payments
                ->unique('payment_method_id')
                ->map(function($payment) {
                    return [
                        'id' => $payment->paymentMethod?->id,
                        'name' => $payment->paymentMethod?->name,
                    ];
                })->values(),
            
            // Último pago
            'last_payment' => $this->when($this->payments->isNotEmpty(), function() {
                $lastPayment = $this->payments->first();
                return [
                    'amount' => number_format($lastPayment->amount, 2, '.', ''),
                    'payment_method' => $lastPayment->paymentMethod?->name,
                    'date' => $lastPayment->payment_date?->format('d/m/Y H:i'),
                ];
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
    
    /**
     * Obtener etiqueta del estado
     */
    private function getStatusLabel()
    {
        $labels = [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'checked_in' => 'En Habitación',
            'checked_out' => 'Finalizado',
            'cancelled' => 'Cancelado',
        ];
        
        return $labels[$this->status] ?? $this->status;
    }
    
    /**
     * Obtener color para el estado
     */
    private function getStatusColor()
    {
        $colors = [
            'pending' => 'warning',
            'confirmed' => 'info',
            'checked_in' => 'success',
            'checked_out' => 'secondary',
            'cancelled' => 'danger',
        ];
        
        return $colors[$this->status] ?? 'default';
    }
    
    /**
     * Obtener clase de badge para el estado
     */
    private function getStatusBadge()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-blue-100 text-blue-800',
            'checked_in' => 'bg-green-100 text-green-800',
            'checked_out' => 'bg-gray-100 text-gray-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];
        
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}