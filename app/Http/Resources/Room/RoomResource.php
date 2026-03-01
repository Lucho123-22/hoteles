<?php

namespace App\Http\Resources\Room;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PricingRange\PricingRangeResource;
use Carbon\Carbon;
use App\Models\PricingRange;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        $currentBooking = $this->currentBooking;

        $remainingTime          = null;
        $remainingSeconds       = null;
        $elapsedTime            = null;
        $elapsedMinutes         = null;
        $isTimeExpired          = false;
        $estimatedCheckout      = null;
        $currentPrice           = null;
        $pricePerMinute         = null;
        $penaltyAmount          = 0;
        $penaltyMinutes         = 0;
        $applicablePricingRange = null;

        // =========================
        // OBTENER POLÍTICAS Y SUB-BRANCH
        // =========================
        $subBranch       = $this->floor?->subBranch;
        $penaltySettings = $subBranch?->penaltySettings;
        $timeSettings    = $subBranch?->timeSettings;

        // =========================
        // CARGAR PRECIOS DISPONIBLES PARA ESTA HABITACIÓN
        // =========================
        $availablePrices = [];
        if ($subBranch && $this->room_type_id) {
            $pricingRanges = isset($this->availablePricingRanges)
                ? $this->availablePricingRanges
                : PricingRange::where('sub_branch_id', $subBranch->id)
                    ->where('room_type_id', $this->room_type_id)
                    ->active()
                    ->effectiveNow()
                    ->with('rateType')
                    ->orderByRaw('time_from_minutes NULLS LAST')
                    ->get();

            $availablePrices = PricingRangeResource::collection($pricingRanges);
        }

        // =========================
        // CALCULO DE TIEMPO Y PRECIO
        // check_out ya viene exacto de la BD (check_in + minutos contratados)
        // No se recalcula, no se suma tolerancia — eso va en finish
        // =========================
        if ($currentBooking && $currentBooking->check_in && $currentBooking->check_out) {

            $tz       = 'America/Lima';
            $checkIn  = Carbon::parse($currentBooking->check_in, $tz)->startOfSecond();
            $now      = Carbon::now($tz)->startOfSecond();

            // ✅ check_out exacto de la BD, sin tocar, sin tolerancia
            $checkOut          = Carbon::parse($currentBooking->check_out, $tz)->startOfSecond();
            $estimatedCheckout = $checkOut->toDateTimeString();

            // ─────────────────────────────────────────
            // TIEMPO TRANSCURRIDO DESDE CHECK-IN
            // ─────────────────────────────────────────
            $totalElapsedSeconds = $now->timestamp - $checkIn->timestamp;

            if ($totalElapsedSeconds < 0) {
                $totalElapsedSeconds = 0;
            }

            $elapsedMinutes = (int) floor($totalElapsedSeconds / 60);
            $elapsedHours   = (int) floor($totalElapsedSeconds / 3600);
            $elapsedMins    = (int) floor(($totalElapsedSeconds % 3600) / 60);
            $elapsedSecs    = (int) ($totalElapsedSeconds % 60);
            $elapsedTime    = sprintf('%02d:%02d:%02d', $elapsedHours, $elapsedMins, $elapsedSecs);

            // ─────────────────────────────────────────
            // TIEMPO RESTANTE HASTA CHECK-OUT
            // Positivo = aún falta | Negativo = ya se pasó
            // ─────────────────────────────────────────
            $remainingSeconds = $checkOut->timestamp - $now->timestamp;
            $isTimeExpired    = $remainingSeconds < 0;

            $absSecs       = abs((int) $remainingSeconds);
            $rHours        = (int) intdiv($absSecs, 3600);
            $rMinutes      = (int) intdiv($absSecs % 3600, 60);
            $rSeconds      = (int) ($absSecs % 60);
            $sign          = $remainingSeconds < 0 ? '-' : '';
            $remainingTime = $sign . sprintf('%02d:%02d:%02d', $rHours, $rMinutes, $rSeconds);

            // ─────────────────────────────────────────
            // PENALIZACIÓN (solo si ya se pasó del check_out)
            // La tolerancia se evalúa en finish, no aquí
            // ─────────────────────────────────────────
            if ($isTimeExpired && $penaltySettings?->penalty_active) {
                $exceededMinutes = (int) ceil($absSecs / 60);
                $intervalMinutes = $penaltySettings->charge_interval_minutes ?? 15;
                $intervals       = (int) ceil($exceededMinutes / $intervalMinutes);
                $penaltyMinutes  = $intervals * $intervalMinutes;

                if ($penaltySettings->penalty_type === 'fixed') {
                    $penaltyAmount = $intervals * (float) ($penaltySettings->amount_per_interval ?? 0);
                } elseif ($penaltySettings->penalty_type === 'percentage') {
                    $basePrice     = (float) ($currentBooking->rate_per_unit ?? 0);
                    $penaltyAmount = $basePrice * ($penaltySettings->amount_per_interval / 100) * $intervals;
                }
            }

            // ─────────────────────────────────────────
            // PRECIO ACTUAL SEGÚN TIEMPO TRANSCURRIDO
            // ─────────────────────────────────────────
            if ($this->room_type_id && $currentBooking->rate_type_id && $subBranch) {
                $pricing = PricingRange::findPrice(
                    $subBranch->id,
                    $this->room_type_id,
                    $currentBooking->rate_type_id,
                    $elapsedMinutes,
                    $now
                );

                if ($pricing) {
                    $currentPrice = (float) $pricing->price;

                    if ($pricing->isHourlyRate() && $pricing->time_to_minutes > $pricing->time_from_minutes) {
                        $totalMinutesInRange = $pricing->time_to_minutes - $pricing->time_from_minutes;
                        $pricePerMinute      = $totalMinutesInRange > 0
                            ? $pricing->price / $totalMinutesInRange
                            : 0;
                    }

                    $applicablePricingRange = [
                        'id'                   => $pricing->id,
                        'time_from_minutes'    => $pricing->time_from_minutes,
                        'time_to_minutes'      => $pricing->time_to_minutes,
                        'formatted_time_range' => $pricing->getFormattedTimeRange(),
                        'price'                => (float) $pricing->price,
                        'rate_type'            => $pricing->rateType?->name,
                        'rate_type_code'       => $pricing->rateType?->code,
                    ];
                }
            }
        }

        return [
            'id'          => $this->id,
            'room_number' => $this->room_number,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            'is_active'   => $this->is_active,
            'full_name'   => $this->full_name,

            'floor' => $this->floor
                ? new FloorResource($this->floor)
                : null,

            'room_type' => $this->roomType
                ? new RoomTypeResource($this->roomType)
                : null,

            'available_pricing_ranges' => $availablePrices,

            'sub_branch_policies' => $subBranch ? [
                'time_settings' => $timeSettings ? [
                    'max_allowed_time' => $timeSettings->max_allowed_time,
                    'extra_tolerance'  => $timeSettings->extra_tolerance,
                    'apply_tolerance'  => $timeSettings->apply_tolerance,
                ] : null,

                'penalty_settings' => $penaltySettings ? [
                    'penalty_active'          => $penaltySettings->penalty_active,
                    'charge_interval_minutes' => $penaltySettings->charge_interval_minutes,
                    'amount_per_interval'     => (float) $penaltySettings->amount_per_interval,
                    'penalty_type'            => $penaltySettings->penalty_type,
                ] : null,

                'checkin_settings' => $subBranch->checkinSettings ? [
                    'checkin_time'       => $subBranch->checkinSettings->checkin_time,
                    'checkout_time'      => $subBranch->checkinSettings->checkout_time,
                    'early_checkin_cost' => (float) $subBranch->checkinSettings->early_checkin_cost,
                    'late_checkout_cost' => (float) $subBranch->checkinSettings->late_checkout_cost,
                ] : null,

                'tax_settings' => $subBranch->taxSettings ? [
                    'tax_percentage' => (float) $subBranch->taxSettings->tax_percentage,
                    'tax_included'   => $subBranch->taxSettings->tax_included,
                ] : null,
            ] : null,

            'current_booking' => $currentBooking ? [
                'booking_id'            => $currentBooking->id,
                'booking_code'          => $currentBooking->booking_code,
                'room_subtotal'         => (float) $currentBooking->room_subtotal,
                'booking_rate_per_unit' => (float) $currentBooking->rate_per_unit,

                'guest_name'      => $currentBooking->customer?->name,
                'guest_client_id' => $currentBooking->customer?->id,
                'guest_document'  => $currentBooking->customer?->document_number,

                'check_in'  => $currentBooking->check_in?->toDateTimeString(),
                'check_out' => $currentBooking->check_out?->toDateTimeString(),

                'total_hours'  => $currentBooking->total_hours,
                'quantity'     => $currentBooking->quantity,
                'rate_type'    => $currentBooking->rateType?->name,
                'rate_type_id' => $currentBooking->rate_type_id,

                // TIEMPO TRANSCURRIDO
                'elapsed_time'    => $elapsedTime,
                'elapsed_minutes' => $elapsedMinutes,

                // TIEMPO RESTANTE
                'remaining_time'     => $remainingTime,
                'remaining_seconds'  => $remainingSeconds,
                'is_time_expired'    => $isTimeExpired,
                'estimated_checkout' => $estimatedCheckout,

                // PRECIO DINÁMICO
                'current_price'            => $currentPrice,
                'price_per_minute'         => $pricePerMinute ? round($pricePerMinute, 2) : null,
                'applicable_pricing_range' => $applicablePricingRange,

                // PENALIZACIÓN
                'penalty_amount'  => round($penaltyAmount, 2),
                'penalty_minutes' => $penaltyMinutes,

                'voucher_type' => $currentBooking->voucher_type,

                'consumptions' => $currentBooking->bookingConsumptions
                    ? $currentBooking->bookingConsumptions->map(function ($consumption) {
                        return [
                            'id'           => $consumption->id,
                            'product_id'   => $consumption->product_id,
                            'product_name' => $consumption->product?->name,
                            'quantity'     => $consumption->quantity,
                            'unit_price'   => (float) $consumption->unit_price,
                            'total_price'  => (float) $consumption->total_price,
                            'status'       => $consumption->status,
                            'consumed_at'  => $consumption->consumed_at?->toDateTimeString(),
                        ];
                    })
                    : [],
            ] : null,

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}