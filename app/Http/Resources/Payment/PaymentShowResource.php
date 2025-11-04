<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Payment;

class PaymentShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $booking = $this->booking;

        return [
            'id' => $this->id,
            'payment_code' => $this->payment_code,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'amount' => (float) $this->amount,
            'exchange_rate' => (float) $this->exchange_rate,
            'amount_base_currency' => (float) $this->amount_base_currency,
            'payment_date' => optional($this->payment_date)->format('d-m-Y H:i:s A'),
            'notes' => $this->notes,
            'reference' => $this->reference,
            'operation_number' => $this->operation_number,
            'created_by' => $this->creadoPor->name.' '.$this->creadoPor->apellidos,
            'currency' => [
                'id' => $this->currency->id ?? null,
                'code' => $this->currency->code ?? 'PEN',
                'name' => $this->currency->name ?? 'Soles',
            ],

            'payment_method' => [
                'id' => $this->paymentMethod->id ?? null,
                'name' => $this->paymentMethod->name ?? $this->payment_method,
            ],

            'cash_register' => [
                'id' => $this->cashRegister->id ?? null,
                'name' => $this->cashRegister->name ?? 'No asignada',
            ],

            // Información de la reserva
            'booking' => $booking ? [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'room_id' => $booking->room_id,
                'client_id' => $booking->client_id,
                'rate_type_id' => $booking->rate_type_id,
                'currency_id' => $booking->currency_id,
                'check_in' => optional($booking->check_in)->format('d-m-Y H:i:s A'),
                'check_out' => optional($booking->check_out)->format('d-m-Y H:i:s A'),
                'total_hours' => (float) $booking->total_hours,
                'rate_per_unit' => (float) $booking->rate_per_unit,
                'rate_per_hour' => (float) $booking->rate_per_hour,
                'room_subtotal' => (float) $booking->room_subtotal,
                'products_subtotal' => (float) $booking->products_subtotal,
                'subtotal' => (float) $booking->subtotal,
                'tax_amount' => (float) $booking->tax_amount,
                'discount_amount' => (float) $booking->discount_amount,
                'total_amount' => (float) $booking->total_amount,
                'paid_amount' => (float) $booking->paid_amount,
                'balance' => (float) ($booking->total_amount - $booking->paid_amount),
                'customers_id' => $booking->customers_id,
                'status' => $booking->status,
                'status_label' => $this->getBookingStatusLabel($booking->status),
                'notes' => $booking->notes,
                'cancelled_at' => optional($booking->cancelled_at)->format('d-m-Y H:i:s A'),
                'cancellation_reason' => $booking->cancellation_reason,
                'cancelled_by' => $booking->cancelled_by,
                'updated_by' => $booking->updated_by,
                'sub_branch_id' => $booking->sub_branch_id,

                'customer' => [
                    'id' => $booking->customer->id ?? null,
                    'name' => $booking->customer->name ?? 'Sin registrar',
                    'document' => $booking->customer->document_number ?? null,
                    'phone' => $booking->customer->phone ?? null,
                ],

                'room' => [
                    'id' => $booking->room->id ?? null,
                    'number' => $booking->room->room_number ?? 'N/A',
                    'name' => $booking->room->name ?? null,
                    'status' => $booking->room->status ?? null,
                    'sub_branch' => [
                        'id' => $booking->room->floor->subBranch->id ?? null,
                        'name' => $booking->room->floor->subBranch->name ?? 'N/A',
                    ],
                ],

                // Lista de consumos
                'consumptions' => $booking->consumptions->map(function ($consumption) {
                    return [
                        'id' => $consumption->id,
                        'product' => $consumption->product->name ?? 'Producto eliminado',
                        'quantity' => (float) $consumption->quantity,
                        'unit_price' => (float) $consumption->unit_price,
                        'total_price' => (float) $consumption->total_price,
                        'consumed_at' => optional($consumption->consumed_at)->format('d-m-Y H:i:s A'),
                    ];
                })->values(),

                // Totales de consumos
                'total_consumos' => (float) $booking->consumptions->sum('total_price'),
            ] : null,

            'created_at' => optional($this->created_at)->format('d-m-Y H:i:s A'),
            'updated_at' => optional($this->updated_at)->format('d-m-Y H:i:s A'),
        ];
    }

    private function getStatusLabel(): string
    {
        return match ($this->status) {
            Payment::STATUS_PENDING => 'Pendiente',
            Payment::STATUS_COMPLETED => 'Completado',
            Payment::STATUS_CANCELLED => 'Cancelado',
            Payment::STATUS_REFUNDED => 'Reembolsado',
            default => ucfirst($this->status ?? 'Desconocido'),
        };
    }

    private function getBookingStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmada',
            'checked_in' => 'En curso (Check-in)',
            'checked_out' => 'Finalizada (Check-out)',
            'cancelled' => 'Cancelada',
            default => ucfirst($status ?? 'Desconocido'),
        };
    }
}
