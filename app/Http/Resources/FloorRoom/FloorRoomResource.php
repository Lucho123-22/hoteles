<?php

namespace App\Http\Resources\FloorRoom;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FloorRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subBranch    = $this->subBranch;
        $timeSettings = $subBranch?->timeSettings;

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'floor_number'    => $this->floor_number,
            'description'     => $this->description,
            'total_rooms'     => $this->rooms->count(),
            'available_rooms' => $this->availableRooms->count(),
            'rooms' => $this->rooms->map(function ($room) use ($timeSettings) {

                $activeBooking = $room->bookings
                    ->whereIn('status', ['checked_in'])
                    ->sortByDesc('check_in')
                    ->first();

                $checkIn          = null;
                $checkOut         = null;
                $estimatedCheckout = null;
                $elapsed          = null;
                $elapsedMinutes   = null;
                $remainingTime    = null;
                $remainingSeconds = null;
                $isTimeExpired    = false;
                $customerName     = null;

                // check_in y check_out ambos deben existir
                if ($activeBooking && $activeBooking->check_in && $activeBooking->check_out) {

                    $tz       = 'America/Lima';
                    $checkIn  = Carbon::parse($activeBooking->check_in, $tz)->startOfSecond();
                    $now      = Carbon::now($tz)->startOfSecond();

                    // ✅ check_out directo de BD — fuente de verdad
                    // No se recalcula, no se suma tolerancia — eso va en finish
                    $checkOut          = Carbon::parse($activeBooking->check_out, $tz)->startOfSecond();
                    $estimatedCheckout = $checkOut->toDateTimeString();

                    // ─────────────────────────────────────────
                    // TIEMPO TRANSCURRIDO DESDE CHECK-IN
                    // ─────────────────────────────────────────
                    $totalElapsedSeconds = $now->timestamp - $checkIn->timestamp;

                    if ($totalElapsedSeconds < 0) {
                        $totalElapsedSeconds = 0;
                        $elapsedMinutes      = 0;
                        $elapsed             = '00:00:00';
                    } else {
                        $elapsedMinutes = (int) floor($totalElapsedSeconds / 60);
                        $elapsedHours   = (int) floor($totalElapsedSeconds / 3600);
                        $elapsedMins    = (int) floor(($totalElapsedSeconds % 3600) / 60);
                        $elapsedSecs    = (int) ($totalElapsedSeconds % 60);
                        $elapsed        = sprintf('%02d:%02d:%02d', $elapsedHours, $elapsedMins, $elapsedSecs);
                    }

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

                    $customerName = $activeBooking->customer?->name;
                }

                return [
                    'id'          => $room->id,
                    'room_number' => $room->room_number,
                    'name'        => $room->name,
                    'status'      => $room->status,
                    'is_active'   => $room->is_active,
                    'room_type'   => $room->roomType?->name,

                    // Tiempos
                    'check_in'           => $checkIn?->toDateTimeString(),
                    'check_out'          => $activeBooking?->check_out?->toDateTimeString(),
                    'estimated_checkout' => $estimatedCheckout,
                    'elapsed_time'       => $elapsed,
                    'elapsed_minutes'    => $elapsedMinutes,
                    'remaining_time'     => $remainingTime,
                    'remaining_seconds'  => $remainingSeconds,
                    'is_time_expired'    => $isTimeExpired,

                    // Cliente y reserva
                    'customer'     => $customerName,
                    'booking_code' => $activeBooking?->booking_code,
                    'booking_id'   => $activeBooking?->id,

                    // Info adicional
                    'total_hours_contracted' => $activeBooking?->total_hours,
                    'rate_type'              => $activeBooking?->rateType?->name,
                ];
            }),
        ];
    }
}