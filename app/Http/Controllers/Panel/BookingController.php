<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RateType;
use App\Models\Product;
use App\Models\Payment;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\FinishBookingRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Models\BookingConsumption;
use App\Models\PricingRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller{
public function store(StoreBookingRequest $request)
{
    try {
        DB::beginTransaction();
        $validated = $request->validated();

        // ============================================================
        // OBTENER CAJA ACTIVA DEL USUARIO AUTENTICADO
        // ============================================================
        $userCashRegister = Auth::user()->getActiveCashRegister();

        if (!$userCashRegister) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una caja registradora abierta. Por favor, abre una caja primero.'
            ], 422);
        }

        // ============================================================
        // VALIDAR HABITACIÓN
        // ============================================================
        $room = Room::findOrFail($validated['room_id']);

        if ($room->status !== Room::STATUS_AVAILABLE) {
            return response()->json([
                'success' => false,
                'message' => 'La habitación no está disponible. Estado actual: ' . $room->status
            ], 422);
        }

        if ($room->hasActiveBooking()) {
            return response()->json([
                'success' => false,
                'message' => 'La habitación ya tiene una reserva activa'
            ], 422);
        }

        // ============================================================
        // OBTENER Y VALIDAR PRICING RANGE
        // ============================================================
        $pricingRange = PricingRange::with('rateType')
            ->findOrFail($validated['pricing_range_id']);

        if ($pricingRange->room_type_id !== $room->room_type_id) {
            return response()->json([
                'success' => false,
                'message' => 'El rango de precio no corresponde al tipo de habitación'
            ], 422);
        }

        if ($pricingRange->rate_type_id !== $validated['rate_type_id']) {
            return response()->json([
                'success' => false,
                'message' => 'El rango de precio no corresponde al tipo de tarifa'
            ], 422);
        }

        if (!$pricingRange->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'El rango de precio seleccionado no está activo'
            ], 422);
        }

        if (!$pricingRange->isEffective()) {
            return response()->json([
                'success' => false,
                'message' => 'El rango de precio seleccionado no está vigente'
            ], 422);
        }

        // ============================================================
        // CALCULAR TIEMPO
        // time_to_minutes es entero exacto (25, 60, 120, 1440, etc.)
        // quantity es el multiplicador del cliente
        // No se divide, no se convierte, no hay políticas aquí
        // ============================================================
        $checkIn      = now();
        $quantity     = $validated['quantity'];

        // Minutos exactos contratados — entero puro
        $totalMinutes = $pricingRange->time_to_minutes * $quantity;

        // check_out exacto sin tolerancia ni políticas
        $checkOut = $checkIn->copy()->addMinutes($totalMinutes);

        // total_hours = referencia en BD, viene del método del modelo
        // getDurationInHours() = (time_to_minutes - time_from_minutes) / 60
        // Para 25min: (25 - 0) / 60 = 0.4166... pero con quantity
        // Para evitar eso, guardamos los minutos directamente como horas multiplicando quantity
        // El campo es referencia, la fuente de verdad es check_in / check_out
        $totalHours = $pricingRange->getDurationInHours() * $quantity;

        // ============================================================
        // CALCULAR PRECIOS
        // ============================================================
        $roomSubtotal = $pricingRange->price * $quantity;
        $ratePerHour  = $pricingRange->price; // precio por unidad contratada

        $productsSubtotal = 0;
        $bookingCode      = $this->generateBookingCode();

        // ============================================================
        // CREAR BOOKING
        // ============================================================
        $booking = Booking::create([
            'id'                => Str::uuid(),
            'booking_code'      => $bookingCode,
            'room_id'           => $validated['room_id'],
            'customers_id'      => $validated['customers_id'],
            'rate_type_id'      => $validated['rate_type_id'],
            'pricing_range_id'  => $validated['pricing_range_id'],
            'currency_id'       => $validated['currency_id'],
            'check_in'          => $checkIn,
            'check_out'         => $checkOut,   // fuente de verdad del tiempo
            'quantity'          => $quantity,
            'total_hours'       => $totalHours, // referencia
            'rate_per_hour'     => $ratePerHour,
            'rate_per_unit'     => $pricingRange->price,
            'room_subtotal'     => $roomSubtotal,
            'products_subtotal' => 0,
            'subtotal'          => $roomSubtotal,
            'tax_amount'        => 0,
            'discount_amount'   => 0,
            'total_amount'      => $roomSubtotal,
            'paid_amount'       => 0,
            'status'            => Booking::STATUS_CONFIRMED,
            'voucher_type'      => $validated['voucher_type'] ?? 'ticket',
            'sub_branch_id'     => Auth::user()->sub_branch_id,
            'created_by'        => Auth::id(),
        ]);

        // ============================================================
        // REGISTRAR CONSUMOS/PRODUCTOS
        // ============================================================
        if (isset($validated['consumptions']) && count($validated['consumptions']) > 0) {
            foreach ($validated['consumptions'] as $consumption) {
                $totalPrice = $consumption['quantity'] * $consumption['unit_price'];

                $booking->consumptions()->create([
                    'id'          => Str::uuid(),
                    'product_id'  => $consumption['product_id'],
                    'quantity'    => $consumption['quantity'],
                    'unit_price'  => $consumption['unit_price'],
                    'total_price' => $totalPrice,
                    'status'      => BookingConsumption::STATUS_PAID,
                    'consumed_at' => now(),
                    'created_by'  => Auth::id(),
                ]);

                $productsSubtotal += $totalPrice;
            }

            $booking->products_subtotal = $productsSubtotal;
            $booking->subtotal          = $roomSubtotal + $productsSubtotal;
            $booking->total_amount      = $booking->subtotal;
            $booking->save();
        }

        // ============================================================
        // REGISTRAR PAGOS CON LA CAJA DEL USUARIO
        // ============================================================
        $totalPaid = 0;

        foreach ($validated['payments'] as $paymentData) {
            $cashRegisterId = $paymentData['cash_register_id'] ?? $userCashRegister->id;

            $cashRegister = CashRegister::with('currentSession')->find($cashRegisterId);

            if (!$cashRegister || !$cashRegister->isOpen()) {
                throw new \Exception('La caja especificada no está abierta');
            }

            if ($cashRegister->currentSession->opened_by !== Auth::id()) {
                throw new \Exception('Solo puedes registrar pagos en tu propia sesión de caja');
            }

            $paymentMethod = PaymentMethod::find($paymentData['payment_method_id']);
            if ($paymentMethod && $paymentMethod->requires_reference && empty($paymentData['operation_number'])) {
                throw new \Exception("El método de pago {$paymentMethod->name} requiere un número de operación");
            }

            Payment::create([
                'id'                   => Str::uuid(),
                'payment_code'         => $this->generatePaymentCode(),
                'booking_id'           => $booking->id,
                'currency_id'          => $validated['currency_id'],
                'amount'               => $paymentData['amount'],
                'amount_base_currency' => $paymentData['amount'],
                'payment_method'       => $paymentMethod->code ?? 'cash',
                'payment_method_id'    => $paymentData['payment_method_id'],
                'cash_register_id'     => $cashRegisterId,
                'operation_number'     => $paymentData['operation_number'] ?? null,
                'payment_date'         => now(),
                'status'               => 'completed',
                'notes'                => 'Pago inicial al check-in',
                'created_by'           => Auth::id(),
            ]);

            $totalPaid += $paymentData['amount'];
        }

        $booking->paid_amount = $totalPaid;
        $booking->save();

        // ============================================================
        // REALIZAR CHECK-IN
        // ============================================================
        $booking->checkIn(Auth::id());

        DB::commit();

        // ============================================================
        // RESPUESTA EXITOSA
        // ============================================================
        return response()->json([
            'success' => true,
            'message' => '✅ Servicio iniciado. Habitación ocupada.',
            'data'    => [
                'booking' => $booking->fresh([
                    'customer',
                    'room',
                    'rateType',
                    'pricingRange.rateType',
                    'currency',
                    'payments.paymentMethod',
                    'consumptions.product'
                ]),
                'check_in'            => $checkIn->toDateTimeString(),
                'check_out_scheduled' => $checkOut->toDateTimeString(),
                'cash_register_used'  => [
                    'id'         => $userCashRegister->id,
                    'name'       => $userCashRegister->name,
                    'session_id' => $userCashRegister->current_session_id
                ],
                'pricing_details' => [
                    'pricing_range_id' => $pricingRange->id,
                    'time_range'       => $pricingRange->getFormattedTimeRange(),
                    'total_minutes'    => $totalMinutes,
                    'price_per_unit'   => $pricingRange->price,
                    'quantity'         => $quantity,
                ],
                'breakdown' => [
                    'rate_per_unit'     => $booking->rate_per_unit,
                    'quantity'          => $quantity,
                    'total_minutes'     => $totalMinutes,
                    'room_subtotal'     => $booking->room_subtotal,
                    'products_subtotal' => $booking->products_subtotal,
                    'subtotal'          => $booking->subtotal,
                    'tax_amount'        => $booking->tax_amount,
                    'discount_amount'   => $booking->discount_amount,
                    'total_amount'      => $booking->total_amount,
                    'paid_amount'       => $booking->paid_amount,
                    'balance'           => $booking->balance,
                ],
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Error al crear booking:', [
            'error'         => $e->getMessage(),
            'trace'         => $e->getTraceAsString(),
            'user_id'       => Auth::id(),
            'sub_branch_id' => Auth::user()->sub_branch_id ?? null,
            'request_data'  => $request->validated()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al iniciar servicio',
            'error'   => $e->getMessage()
        ], 500);
    }
}

public function finishService(FinishBookingRequest $request, Booking $booking)
{
    try {
        DB::beginTransaction();

        if ($booking->status !== Booking::STATUS_CHECKED_IN) {
            return response()->json([
                'success' => false,
                'message' => 'La reserva debe estar activa para finalizarla'
            ], 422);
        }

        // ============================================================
        // CARGAR POLÍTICAS DE LA SUB-SUCURSAL
        // ============================================================
        $subBranch = $booking->room->floor->subBranch->load([
            'timeSettings',
            'penaltySettings',
            'taxSettings',
        ]);

        $timeSettings    = $subBranch->timeSettings;
        $penaltySettings = $subBranch->penaltySettings;
        $taxSettings     = $subBranch->taxSettings;

        // ============================================================
        // CALCULAR TIEMPO
        // ============================================================
        $tz            = 'America/Lima';
        $now           = Carbon::now($tz)->startOfSecond();
        $checkIn       = Carbon::parse($booking->check_in, $tz)->startOfSecond();
        $checkOut      = Carbon::parse($booking->check_out, $tz)->startOfSecond();
        $minutosUsados = $checkIn->diffInMinutes($now);

        // ============================================================
        // EVALUAR TOLERANCIA Y PENALIZACIÓN
        // ============================================================
        $applyTolerance        = $timeSettings?->apply_tolerance ?? false;
        $toleranceMinutes      = $applyTolerance ? ($timeSettings->extra_tolerance ?? 0) : 0;
        $checkOutConTolerancia = $checkOut->copy()->addMinutes($toleranceMinutes);

        $sePaso           = $now->greaterThan($checkOut);
        $dentroTolerancia = $sePaso && $now->lessThanOrEqualTo($checkOutConTolerancia);
        $fueraToleranacia = $now->greaterThan($checkOutConTolerancia);

        $penaltyAmount    = 0;
        $penaltyMinutes   = 0;
        $penaltyIntervals = 0;

        if ($fueraToleranacia && $penaltySettings?->penalty_active) {
            $minutosExcedidos = $checkOutConTolerancia->diffInMinutes($now);
            $intervalMinutes  = $penaltySettings->charge_interval_minutes ?? 15;
            $penaltyIntervals = (int) ceil($minutosExcedidos / $intervalMinutes);
            $penaltyMinutes   = $penaltyIntervals * $intervalMinutes;

            if ($penaltySettings->penalty_type === 'fixed') {
                $penaltyAmount = $penaltyIntervals * (float) $penaltySettings->amount_per_interval;
            } elseif ($penaltySettings->penalty_type === 'percentage') {
                $basePrice     = (float) $booking->rate_per_unit;
                $penaltyAmount = $basePrice * ((float) $penaltySettings->amount_per_interval / 100) * $penaltyIntervals;
            }

            $penaltyAmount = round($penaltyAmount, 2);
        }

        // ============================================================
        // CONSUMOS PENDING → calcular subtotal adicional
        // ============================================================
        $consumosPending = $booking->consumptions()
            ->where('status', BookingConsumption::STATUS_PENDING)
            ->get();

        $pendingSubtotal = $consumosPending->sum('total_price');

        // ============================================================
        // RECALCULAR TOTALES REALES DEL BOOKING
        // ============================================================
        $nuevoProductsSubtotal = (float) $booking->products_subtotal + (float) $pendingSubtotal;
        $nuevoSubtotal         = (float) $booking->room_subtotal + $nuevoProductsSubtotal;
        $nuevoTotal            = $nuevoSubtotal + $penaltyAmount;

        // ACTUALIZAR booking con nuevos totales
        $booking->products_subtotal = $nuevoProductsSubtotal;
        $booking->penalty_amount    = $penaltyAmount;
        $booking->subtotal          = $nuevoSubtotal;
        $booking->total_amount      = $nuevoTotal;
        $booking->paid_amount       = round($nuevoTotal, 2);
        $booking->save();

        // ============================================================
        // MARCAR CONSUMOS PENDING → PAID
        // ============================================================
        $booking->consumptions()
            ->where('status', BookingConsumption::STATUS_PENDING)
            ->update(['status' => BookingConsumption::STATUS_PAID]);

        // ============================================================
        // ACTUALIZAR EL PAYMENT EXISTENTE (no crear uno nuevo)
        // ============================================================
        $notasPago = [];
        if ($pendingSubtotal > 0) $notasPago[] = "Consumos: S/. {$pendingSubtotal}";
        if ($penaltyAmount > 0)   $notasPago[] = "Penalización: S/. {$penaltyAmount}";
        $notaDescriptiva = count($notasPago) > 0
            ? 'Actualizado al finalizar (' . implode(', ', $notasPago) . ')'
            : 'Actualizado al finalizar servicio';

        $booking->payments()
            ->where('status', 'completed')
            ->latest('payment_date')
            ->first()
            ?->update([
                'amount'               => round($nuevoTotal, 2),
                'amount_base_currency' => round($nuevoTotal, 2),
                'notes'                => $notaDescriptiva,
            ]);

        // ============================================================
        // FINALIZAR BOOKING
        // ============================================================
        $booking->actual_check_out = $now;
        $booking->actual_hours     = (int) ceil($minutosUsados / 60);
        $booking->finish_type      = 'manual';
        $booking->finished_by      = Auth::id();

        if ($request->notes) {
            $booking->notes = ($booking->notes ? $booking->notes . "\n" : '')
                . "[{$now->format('Y-m-d H:i')}] " . $request->notes;
        }

        $booking->save();

        // Cambiar estado habitación → limpieza
        $booking->checkOut(Auth::id());

        DB::commit();

        $balanceFinal = round((float) $booking->total_amount - (float) $booking->paid_amount, 2);

        return response()->json([
            'success' => true,
            'message' => '✅ Servicio finalizado. Habitación en limpieza.',
            'data'    => [
                'booking'        => $booking->fresh(['room', 'customer', 'consumptions', 'payments']),
                'check_out_real' => $now->toDateTimeString(),
                'tiempo' => [
                    'check_in'             => $checkIn->toDateTimeString(),
                    'check_out_contratado' => $checkOut->toDateTimeString(),
                    'check_out_real'       => $now->toDateTimeString(),
                    'minutos_contratados'  => $checkIn->diffInMinutes($checkOut),
                    'minutos_usados'       => $minutosUsados,
                    'se_paso'              => $sePaso,
                    'dentro_tolerancia'    => $dentroTolerancia,
                    'fuera_tolerancia'     => $fueraToleranacia,
                    'tolerancia_minutos'   => $toleranceMinutes,
                    'penalizacion_minutos' => $penaltyMinutes,
                ],
                'financiero' => [
                    'room_subtotal'     => round((float) $booking->room_subtotal, 2),
                    'products_subtotal' => round((float) $booking->products_subtotal, 2),
                    'pending_cobrado'   => round((float) $pendingSubtotal, 2),
                    'penalty_amount'    => $penaltyAmount,
                    'subtotal'          => round((float) $booking->subtotal, 2),
                    'total_amount'      => round((float) $booking->total_amount, 2),
                    'paid_amount'       => round((float) $booking->paid_amount, 2),
                    'balance'           => $balanceFinal,
                ],
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Error al finalizar booking:', [
            'booking_id' => $booking->id,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al finalizar servicio',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    public function addConsumption(Request $request, string $bookingId)
{
    $validated = $request->validate([
        'consumptions'              => ['required', 'array', 'min:1'],
        'consumptions.*.product_id' => ['required', 'uuid', 'exists:products,id'],
        'consumptions.*.quantity'   => ['required', 'numeric', 'min:0.01'],
        'consumptions.*.unit_price' => ['required', 'numeric', 'min:0'],
    ]);

    $booking = Booking::findOrFail($bookingId);

    if ($booking->status !== Booking::STATUS_CHECKED_IN) {
        return response()->json([
            'success' => false,
            'message' => 'Solo se pueden agregar productos a una reserva activa.'
        ], 422);
    }

    try {
        DB::beginTransaction();

        foreach ($validated['consumptions'] as $item) {

            $booking->consumptions()->create([
                'id'          => Str::uuid(),
                'product_id'  => $item['product_id'],
                'quantity'    => (float) $item['quantity'],
                'unit_price'  => (float) $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
                'status'      => BookingConsumption::STATUS_PENDING,
                'consumed_at' => now(),
                'created_by'  => Auth::id(),
            ]);

            // 🔥 Aquí NO tocamos bookings
            // 🔥 El observer se encarga del kardex
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Producto(s) agregado(s).'
        ], 201);

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Error al agregar producto(s)',
        ], 500);
    }
}
    private function generateBookingCode(): string
    {
        return 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function generatePaymentCode(): string
    {
        return 'PAY-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
    }

    public function getCheckoutDetails($roomId){
        try {
            $room = Room::with(['activeBooking.customer', 'activeBooking.consumptions.product'])
                ->findOrFail($roomId);
            if (!$room->activeBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay reserva activa en esta habitación'
                ], 404);
            }
            $booking = $room->activeBooking;
            $checkInTime = $booking->check_in;
            $now = now();
            $minutesUsed = $checkInTime->diffInMinutes($now);
            $hoursUsed = $minutesUsed / 60;
            $hoursContracted = $booking->total_hours;
            $extraHours = max(0, $hoursUsed - $hoursContracted);
            $extraAmount = 0;
            if ($extraHours > 0) {
                $extraHoursCeil = ceil($extraHours);
                $extraAmount = $extraHoursCeil * $booking->rate_per_hour;
            }
            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => $booking->customer->name ?? 'Sin cliente',
                    'check_in_formatted' => $checkInTime->format('d-m-Y H:i:s A'),
                    'total_time' => sprintf('%dh %dm', floor($hoursUsed), $minutesUsed % 60),
                    'has_extra_charges' => $extraAmount > 0,
                    'extra_charges' => number_format($extraAmount, 2),
                    'total_amount' => $booking->total_amount + $extraAmount,
                    'paid_amount' => $booking->paid_amount,
                    'balance' => ($booking->total_amount + $extraAmount) - $booking->paid_amount,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener detalles de checkout:', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles de la habitación'
            ], 500);
        }
    }
    /**
     * COBRAR TIEMPO EXTRA
     * Cobra el tiempo extra y extiende el checkout
     */
    public function chargeExtraTime(Request $request, $roomId){
        try {
            DB::beginTransaction();
            $room = Room::with('activeBooking')->findOrFail($roomId);
            if (!$room->activeBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay reserva activa en esta habitación'
                ], 404);
            }
            $booking = $room->activeBooking;
            $checkOutScheduled = $booking->check_out;
            $now = now();
            
            if ($now <= $checkOutScheduled) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tiempo aún no ha vencido'
                ], 422);
            }

            $extraMinutes = $checkOutScheduled->diffInMinutes($now);
            $extraHours = $extraMinutes / 60;
            $extraHoursCeil = ceil($extraHours);
            $extraAmount = $extraHoursCeil * $booking->rate_per_hour;

            $booking->total_hours += $extraHoursCeil;
            $booking->check_out = $now->copy()->addHours($extraHoursCeil);
            $booking->room_subtotal += $extraAmount;
            $booking->subtotal += $extraAmount;
            $booking->total_amount += $extraAmount;
            $booking->notes = ($booking->notes ?? '') . "\n[" . $now . "] Cobro tiempo extra: {$extraHoursCeil}h = S/ {$extraAmount}";
            $booking->updated_by = Auth::id();
            $booking->save();
            $booking->room->statusLogs()->create([
                'id' => Str::uuid(),
                'room_id' => $booking->room_id,
                'booking_id' => $booking->id,
                'previous_status' => Room::STATUS_OCCUPIED,
                'new_status' => Room::STATUS_OCCUPIED,
                'reason' => "Cobro tiempo extra: +{$extraHoursCeil}h = S/ {$extraAmount}",
                'changed_at' => $now,
                'changed_by' => Auth::id(),
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Tiempo extra cobrado: {$extraHoursCeil}h por S/ {$extraAmount}. Nuevo checkout programado.",
                'data' => [
                    'booking' => $booking->fresh(['room', 'customer']),
                    'extra_hours' => $extraHoursCeil,
                    'extra_amount' => $extraAmount,
                    'new_total' => $booking->total_amount,
                    'new_balance' => $booking->balance,
                    'new_checkout' => $booking->check_out->toDateTimeString()
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al cobrar tiempo extra:', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cobrar tiempo extra'
            ], 500);
        }
    }
    public function checkout($roomId){
        try {
            DB::beginTransaction();
            $room = Room::with('activeBooking.customer')->findOrFail($roomId);
            if (!$room->activeBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay reserva activa en esta habitación'
                ], 404);
            }
            $booking = $room->activeBooking;
            if ($booking->status !== Booking::STATUS_CHECKED_IN) {
                return response()->json([
                    'success' => false,
                    'message' => 'La reserva debe estar activa para finalizarla'
                ], 422);
            }
            $checkOutReal = now();
            $checkInTime = $booking->check_in;
            $hoursUsedReal = $checkInTime->diffInMinutes($checkOutReal) / 60;
            $hoursContracted = $booking->total_hours;
            $extraHours = max(0, $hoursUsedReal - $hoursContracted);
            if ($extraHours > 0) {
                $extraHoursCeil = ceil($extraHours);
                $extraAmount = $extraHoursCeil * $booking->rate_per_hour;
                
                $booking->total_hours += $extraHoursCeil;
                $booking->room_subtotal += $extraAmount;
                $booking->subtotal += $extraAmount;
                $booking->total_amount += $extraAmount;
                $booking->notes = ($booking->notes ?? '') . "\n[" . $checkOutReal . "] Tiempo extra al checkout: {$extraHoursCeil}h = S/ {$extraAmount}";
            }
            $booking->save();
            $booking->checkOut(Auth::id());
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Reserva finalizada correctamente. Habitación en limpieza.',
                'data' => [
                    'booking' => $booking->fresh(['room', 'customer', 'consumptions', 'payments']),
                    'check_out_time' => $checkOutReal->toDateTimeString(),
                    'final_balance' => $booking->balance,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al hacer checkout:', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar la reserva'
            ], 500);
        }
    }
    /**
     * GENERAR TICKET/COMPROBANTE
     * Retorna los datos formateados para el ticket
     */
    public function ticket($bookingId){
        try {
            $booking = Booking::with([
                'customer',
                'room.roomType',
                'rateType',
                'currency',
                'consumptions.product',
                'payments.paymentMethod',
                'subBranch.branch'
            ])->findOrFail($bookingId);
            $branch = $booking->subBranch->branch ?? null;
            $empresa = [
                'nombre' => $branch->name ?? 'HOTEL',
                'ruc' => $branch->ruc ?? '20000000000',
                'direccion' => $branch->address ?? 'Sin dirección',
                'telefono' => $branch->phone ?? 'Sin teléfono'
            ];
            $comprobante = [
                'tipo' => strtoupper($booking->voucher_type),
                'numero' => $booking->booking_code,
                'fecha' => $booking->check_in->format('d/m/Y'),
                'hora' => $booking->check_in->format('H:i:s')
            ];
            $cliente = [
                'nombre' => $booking->customer->full_name ?? 'Cliente General',
                'documento' => ($booking->customer->document_type ?? 'DNI') . ': ' . ($booking->customer->document_number ?? 'Sin documento'),
                'direccion' => $booking->customer->address ?? ''
            ];
            $habitacion = [
                'numero' => $booking->room->room_number,
                'tipo' => $booking->room->roomType->name ?? 'Habitación',
                'tarifa' => $booking->rateType->name ?? 'Por Hora',
                'cantidad' => $booking->total_hours,
                'precioUnitario' => (float) $booking->rate_per_hour,
                'total' => (float) $booking->room_subtotal
            ];
            $productos = [];
            foreach ($booking->consumptions as $consumption) {
                $productos[] = [
                    'nombre' => $consumption->product->name ?? 'Producto',
                    'cantidad' => (float) $consumption->quantity,
                    'precio' => (float) $consumption->unit_price,
                    'total' => (float) $consumption->total_price
                ];
            }
            $totales = [
                'subtotal' => (float) $booking->subtotal,
                'descuento' => (float) $booking->discount_amount,
                'igv' => (float) $booking->tax_amount,
                'total' => (float) $booking->total_amount
            ];
            $primerPago = $booking->payments->first();
            $pago = [
                'metodo' => $primerPago ? $primerPago->paymentMethod->name ?? 'Efectivo' : 'Efectivo',
                'operacion' => $primerPago ? $primerPago->operation_number : null
            ];
            $footer = [
                'mensaje' => '¡Esperamos su próxima visita!',
                'sistema' => config('app.name', 'Sistema Hotelero') . ' v1.0'
            ];
            return response()->json([
                'success' => true,
                'data' => [
                    'empresa' => $empresa,
                    'comprobante' => $comprobante,
                    'cliente' => $cliente,
                    'habitacion' => $habitacion,
                    'productos' => $productos,
                    'totales' => $totales,
                    'pago' => $pago,
                    'footer' => $footer
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al generar ticket:', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request)
    {
        $query = Booking::with([
            'room',
            'customer',
            'rateType',
            'payments.paymentMethod'
        ]);

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('room', function($q) use ($search) {
                        $q->where('room_number', 'like', "%{$search}%");
                    });
            });
        }

        // Filtro por método de pago
        if ($request->filled('payment_method_id')) {
            $query->whereHas('payments', function($q) use ($request) {
                $q->where('payment_method_id', $request->payment_method_id);
            });
        }

        // Filtro por rango de fechas
        if ($request->filled('date_from')) {
            $query->whereDate('check_in', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('check_in', '<=', $request->date_to);
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por sucursal
        if ($request->filled('sub_branch_id')) {
            $query->where('sub_branch_id', $request->sub_branch_id);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $bookings = $query->paginate($perPage);

        // Mapear los datos simplificados
        $data = $bookings->map(function($booking) {
            $payment = $booking->payments->first();
            $quantityLabel = $this->getQuantityLabel($booking);

            return [
                'id' => $booking->id,
                'payment_code' => $payment ? $payment->payment_code : 'Sin pago',
                'booking_code' => $booking->booking_code,
                'habitacion' => $booking->room->room_number ?? 'N/A',
                'cliente' => $booking->customer->name ?? 'Sin cliente',
                'fecha' => $booking->check_in ? $booking->check_in->format('d/m/Y H:i') : null,
                
                // ✅ PRECIO, CANTIDAD Y TOTAL
                'precio_unitario' => (float) $booking->rate_per_hour,  // Precio por hora/día/noche
                'quantity' => $booking->quantity,  // Cantidad (5, 7, 3)
                'quantity_label' => $quantityLabel,  // "5 hora(s)", "7 día(s)"
                'total_hours' => $booking->total_hours,  // Horas totales
                'monto_total' => (float) $booking->room_subtotal,  // Total = precio × quantity
                
                'rate_type' => $booking->rateType ? [
                    'name' => $booking->rateType->name,
                    'code' => $booking->rateType->code,
                    'duration_hours' => $booking->rateType->duration_hours,
                ] : null,
                
                'metodo_pago' => $payment && $payment->paymentMethod ? $payment->paymentMethod->name : 'N/A',
                'estado' => $booking->status,
                'estado_label' => $this->getStatusLabel($booking->status),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'from' => $bookings->firstItem(),
                'to' => $bookings->lastItem(),
            ],
        ]);
    }

    /**
     * ✅ Obtener etiqueta formateada de la cantidad
     */
    private function getQuantityLabel($booking)
    {
        if (!$booking->rateType || !$booking->quantity) {
            return $booking->quantity . ' unidad(es)';
        }

        $unit = strtoupper($booking->rateType->code);
        
        switch($unit) {
            case 'HOUR':
            case 'HOURLY':
                return $booking->quantity . ' hora(s)';
            case 'DAY':
            case 'DAILY':
                return $booking->quantity . ' día(s)';
            case 'NIGHT':
                return $booking->quantity . ' noche(s)';
            case '12HOURS':
            case 'HALF_DAY':
                return $booking->quantity . ' bloque(s) de 12h';
            case '8HOURS':
                return $booking->quantity . ' bloque(s) de 8h';
            default:
                return $booking->quantity . ' unidad(es)';
        }
    }
    /**
     * Extender tiempo de la reserva
     * Maneja extensión anticipada o regularización de tiempo ya pasado
     */
    public function extenderTiempo(Request $request, Booking $booking)
{
    $request->validate([
        'horas_adicionales' => 'required|numeric|min:0.5',
    ]);

    if ($booking->status !== Booking::STATUS_CHECKED_IN) {
        return response()->json([
            'success' => false,
            'message' => 'Solo se puede extender tiempo en reservas activas'
        ], 422);
    }

    try {
        DB::beginTransaction();

        $ahora = now();
        $checkOutProgramado = $booking->check_out;
        $horasAdicionales = $request->horas_adicionales;
        
        // ============================================
        // 1. CALCULAR HORAS REALES ACTUALES (positivas)
        // ============================================
        $horasUsadasHastaAhora = $booking->check_in->diffInHours($ahora);
        $horasContratadasOriginales = $booking->check_in->diffInHours($checkOutProgramado);
        
        // ============================================
        // 2. VERIFICAR SI SE PASÓ DEL TIEMPO
        // ============================================
        $yaSePaso = $ahora->greaterThan($checkOutProgramado);
        $horasExcedidas = 0;
        $costoExcedido = 0;
        
        if ($yaSePaso) {
            $horasExcedidas = ceil($ahora->diffInHours($checkOutProgramado));
            $costoExcedido = $booking->rate_per_hour * $horasExcedidas;
            
            // Actualizar con el tiempo excedido
            $booking->total_hours = $horasContratadasOriginales + $horasExcedidas;
            $booking->room_subtotal = $booking->total_hours * $booking->rate_per_hour;
            
            $booking->notes = ($booking->notes ?? '') . 
                "\n[" . $ahora->format('Y-m-d H:i') . "] ⚠️ Regularización: {$horasExcedidas}h ya usadas = S/ {$costoExcedido}";
        }
        
        // ============================================
        // 3. EXTENDER EL TIEMPO
        // ============================================
        // Extender desde el checkout programado (no desde ahora)
        $nuevoCheckOut = $checkOutProgramado->copy()->addHours($horasAdicionales);
        $costoExtension = $booking->rate_per_hour * $horasAdicionales;
        
        // Guardar checkout anterior
        $checkOutAnterior = $booking->check_out;
        
        // ============================================
        // 4. ACTUALIZAR BOOKING CON VALORES CORRECTOS
        // ============================================
        $booking->check_out = $nuevoCheckOut;
        
        // Calcular TOTAL de horas (originales + excedidas + extendidas)
        $horasTotalesNuevas = $horasContratadasOriginales + $horasExcedidas + $horasAdicionales;
        
        // Asegurar que sean positivos
        $booking->total_hours = max(0, $horasTotalesNuevas);
        $booking->room_subtotal = $booking->total_hours * $booking->rate_per_hour;
        
        // Recalcular todos los totales
        $booking->subtotal = $booking->room_subtotal + $booking->products_subtotal;
        $booking->total_amount = $booking->subtotal + $booking->tax_amount - $booking->discount_amount;
        
        $booking->updated_by = auth()->id();
        $booking->notes = ($booking->notes ?? '') . 
            "\n[" . $ahora->format('Y-m-d H:i') . "] ✅ Extensión: +{$horasAdicionales}h. " .
            "Nuevo checkout: " . $nuevoCheckOut->format('d-m-Y H:i A') .
            "\nHoras totales: {$booking->total_hours}h, Subtotal: S/ {$booking->room_subtotal}";
        
        $booking->save();

        DB::commit();

        // ============================================
        // 5. RESPUESTA
        // ============================================
        $respuesta = [
            'success' => true,
            'message' => $yaSePaso 
                ? "Se regularizó {$horasExcedidas}h excedidas y se extendió {$horasAdicionales}h"
                : "Tiempo extendido por {$horasAdicionales}h",
            'data' => [
                'booking_code' => $booking->booking_code,
                'check_in' => $booking->check_in->format('d-m-Y H:i A'),
                'checkout_anterior' => $checkOutAnterior->format('d-m-Y H:i A'),
                'checkout_nuevo' => $nuevoCheckOut->format('d-m-Y H:i A'),
                'horas_adicionales' => $horasAdicionales,
                'costo_extension' => round($costoExtension, 2),
                'resumen_financiero' => [
                    'horas_totales' => $booking->total_hours,
                    'dias_totales' => round($booking->total_hours / 24, 1),
                    'room_subtotal' => round($booking->room_subtotal, 2),
                    'total_amount' => round($booking->total_amount, 2),
                    'paid_amount' => round($booking->paid_amount, 2),
                    'saldo_pendiente' => round($booking->total_amount - $booking->paid_amount, 2),
                ]
            ]
        ];
        
        if ($yaSePaso) {
            $respuesta['data']['regularizacion'] = [
                'horas_excedidas' => $horasExcedidas,
                'costo_excedido' => round($costoExcedido, 2),
                'total_adicional' => round($costoExcedido + $costoExtension, 2),
            ];
        }
        
        return response()->json($respuesta);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error al extender tiempo:', [
            'booking_id' => $booking->id,
            'error' => $e->getMessage(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error al extender tiempo',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function show(Booking $booking)
{
    try {
        // Cargar relaciones
        $booking->load([
            'room.floor.subBranch.branch',
            'customer',
            'rateType',
            'currency',
            'consumptions.product',
            'payments.paymentMethod',
            'payments.currency',
            'subBranch',
        ]);

        $ahora = now();
        $checkInTime = $booking->check_in;
        $checkOutProgramado = $booking->check_out;
        
        // ============================================
        // CALCULAR TIEMPO TRANSCURRIDO
        // ============================================
        $minutosTranscurridos = $checkInTime->diffInMinutes($ahora);
        $horasTranscurridas = $minutosTranscurridos / 60;
        
        // ============================================
        // VERIFICAR SI YA SE PASÓ DEL TIEMPO
        // ============================================
        $yaSePaso = $ahora->greaterThan($checkOutProgramado);
        
        // ✅ CORRECCIÓN: Calcular minutos extra correctamente
        if ($yaSePaso) {
            // Si ya se pasó, calcular cuántos minutos DESPUÉS del checkout programado
            $minutosExtra = $checkOutProgramado->diffInMinutes($ahora); // ← Invertido
            $horasExtra = ceil($minutosExtra / 60);
            $costoTiempoExtra = $horasExtra * $booking->rate_per_hour;
            $minutosRestantes = 0;
            $horasRestantes = 0;
        } else {
            // Si NO se pasó, calcular tiempo restante
            $minutosExtra = 0;
            $horasExtra = 0;
            $costoTiempoExtra = 0;
            $minutosRestantes = $ahora->diffInMinutes($checkOutProgramado);
            $horasRestantes = $minutosRestantes / 60;
        }
        
        // ============================================
        // CALCULAR TOTALES FINANCIEROS
        // ============================================
        $totalPagado = $booking->payments()
            ->where('status', 'completed')
            ->sum('amount');
        
        $saldoPendiente = $booking->total_amount - $totalPagado;
        
        // Si hay tiempo extra, calcular el nuevo total
        $totalConTiempoExtra = $booking->total_amount + $costoTiempoExtra;
        $saldoConExtra = $totalConTiempoExtra - $totalPagado;

        // ============================================
        // DETERMINAR ACCIONES DISPONIBLES
        // ============================================
        $accionesDisponibles = [
            'puede_extender' => $booking->status === Booking::STATUS_CHECKED_IN,
            'puede_finalizar' => $booking->status === Booking::STATUS_CHECKED_IN,
            'puede_agregar_consumo' => $booking->status === Booking::STATUS_CHECKED_IN,
            'puede_cancelar' => in_array($booking->status, [
                Booking::STATUS_PENDING, 
                Booking::STATUS_CONFIRMED, 
                Booking::STATUS_CHECKED_IN
            ]),
            'requiere_pago' => $saldoConExtra > 0, // ← Importante: usar saldo CON extra
        ];

        // ============================================
        // PREPARAR RESPUESTA
        // ============================================
        return response()->json([
            'success' => true,
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'status' => $booking->status,
                    'status_label' => $this->getStatusLabel($booking->status),
                    'check_in' => $booking->check_in->format('Y-m-d H:i:s'),
                    'check_out' => $booking->check_out->format('Y-m-d H:i:s'),
                    'actual_check_out' => $booking->actual_check_out?->format('Y-m-d H:i:s'),
                    'total_hours' => $booking->total_hours,
                    'actual_hours' => $booking->actual_hours,
                    'rate_per_hour' => (float) $booking->rate_per_hour,
                    'notes' => $booking->notes,
                ],
                'room' => [
                    'id' => $booking->room->id,
                    'room_number' => $booking->room->room_number,
                    'name' => $booking->room->name,
                    'status' => $booking->room->status,
                    'floor' => $booking->room->floor->name ?? null,
                    'sub_branch' => $booking->room->floor->subBranch->name ?? null,
                    'branch' => $booking->room->floor->subBranch->branch->name ?? null,
                ],
                'customer' => [
                    'id' => $booking->customer->id,
                    'name' => $booking->customer->name,
                    'document_type' => $booking->customer->document_type ?? null,
                    'document_number' => $booking->customer->document_number ?? null,
                    'phone' => $booking->customer->phone ?? null,
                    'email' => $booking->customer->email ?? null,
                ],
                'rate_type' => [
                    'id' => $booking->rateType->id,
                    'name' => $booking->rateType->name,
                    'code' => $booking->rateType->code,
                    'duration_hours' => $booking->rateType->duration_hours,
                ],
                'currency' => [
                    'id' => $booking->currency->id,
                    'code' => $booking->currency->code,
                    'symbol' => $booking->currency->symbol,
                ],
                'consumptions' => $booking->consumptions->map(function($consumption) {
                    return [
                        'id' => $consumption->id,
                        'product_id' => $consumption->product_id,
                        'product_name' => $consumption->product->name,
                        'quantity' => (float) $consumption->quantity,
                        'unit_price' => (float) $consumption->unit_price,
                        'total_price' => (float) $consumption->total_price,
                        'consumed_at' => $consumption->consumed_at->format('Y-m-d H:i:s'),
                        'status' => $consumption->status,
                    ];
                }),
                'payments' => $booking->payments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_code' => $payment->payment_code,
                        'amount' => (float) $payment->amount,
                        'payment_method' => $payment->paymentMethod->name ?? $payment->payment_method,
                        'payment_date' => $payment->payment_date->format('Y-m-d H:i:s'),
                        'status' => $payment->status,
                        'operation_number' => $payment->operation_number,
                        'currency' => $payment->currency->code ?? 'PEN',
                    ];
                }),
                'financial_summary' => [
                    'room_subtotal' => (float) $booking->room_subtotal,
                    'products_subtotal' => (float) $booking->products_subtotal,
                    'tax_amount' => (float) $booking->tax_amount,
                    'discount_amount' => (float) $booking->discount_amount,
                    'subtotal' => (float) $booking->subtotal,
                    'total_amount' => (float) $booking->total_amount,
                    'paid_amount' => (float) $totalPagado,
                    'balance' => (float) $saldoPendiente,
                    
                    // Tiempo extra
                    'tiene_tiempo_extra' => $horasExtra > 0,
                    'costo_tiempo_extra' => (float) $costoTiempoExtra,
                    'total_con_extra' => (float) $totalConTiempoExtra,
                    'saldo_con_extra' => (float) $saldoConExtra,
                ],
                'time_info' => [
                    'check_in_formatted' => $checkInTime->format('d-m-Y H:i A'),
                    'check_out_programado_formatted' => $checkOutProgramado->format('d-m-Y H:i A'),
                    'hora_actual' => $ahora->format('d-m-Y H:i A'),
                    
                    // Tiempo transcurrido desde check-in
                    'minutos_transcurridos' => $minutosTranscurridos,
                    'horas_transcurridas' => round($horasTranscurridas, 2),
                    
                    // ¿Ya se pasó?
                    'ya_se_paso_del_tiempo' => $yaSePaso,
                    'minutos_extra' => $minutosExtra,
                    'horas_extra' => $horasExtra,
                    'costo_horas_extra' => (float) $costoTiempoExtra,
                    
                    // Tiempo restante (si no se pasó)
                    'minutos_restantes' => $minutosRestantes,
                    'horas_restantes' => round($horasRestantes, 2),
                    
                    // Comparación
                    'horas_contratadas' => $booking->total_hours,
                    'horas_usadas' => round($horasTranscurridas, 2),
                    'porcentaje_usado' => round(($horasTranscurridas / $booking->total_hours) * 100, 2),
                ],
                'actions' => $accionesDisponibles,
                'alerts' => $this->generateAlerts(
                    $booking, 
                    $yaSePaso, 
                    $horasExtra, 
                    $saldoConExtra, // ← Usar saldo CON extra
                    $minutosRestantes
                ),
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error al mostrar booking:', [
            'booking_id' => $booking->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar la reserva',
            'error' => $e->getMessage()
        ], 500);
    }
}

// ============================================
// MÉTODOS AUXILIARES
// ============================================
private function getStatusLabel($status)
{
    return match($status) {
        Booking::STATUS_PENDING => 'Pendiente',
        Booking::STATUS_CONFIRMED => 'Confirmada',
        Booking::STATUS_CHECKED_IN => 'Activa',
        Booking::STATUS_CHECKED_OUT => 'Finalizada',
        Booking::STATUS_CANCELLED => 'Cancelada',
        default => 'Desconocido',
    };
}

private function generateAlerts($booking, $yaSePaso, $horasExtra, $saldoPendiente, $minutosRestantes)
{
    $alerts = [];
    
    // ✅ ALERTA CRÍTICA: Tiempo excedido
    if ($yaSePaso && $horasExtra > 0) {
        $dias = intdiv($horasExtra, 24);
        $horasRestantes = $horasExtra % 24;
        
        $tiempoExcedidoTexto = $dias > 0 
            ? "{$dias} día(s) y {$horasRestantes} hora(s)" 
            : "{$horasExtra} hora(s)";
        
        $costoExtra = $horasExtra * $booking->rate_per_hour;
        
        $alerts[] = [
            'type' => 'danger',
            'icon' => '⚠️',
            'title' => '¡TIEMPO EXCEDIDO!',
            'message' => "El cliente se ha excedido {$tiempoExcedidoTexto} del tiempo contratado",
            'detail' => "Costo adicional: S/ " . number_format($costoExtra, 2),
            'action' => 'extend_or_finish',
            'action_text' => 'Extender tiempo o Finalizar servicio',
        ];
    }
    
    // ⚠️ ALERTA: Tiempo por vencer (menos de 30 minutos)
    if (!$yaSePaso && $minutosRestantes <= 30 && $minutosRestantes > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => '⏰',
            'title' => 'Tiempo por vencer',
            'message' => "Quedan solo " . round($minutosRestantes) . " minutos para el checkout",
            'action' => 'extend_time',
            'action_text' => 'Extender tiempo',
        ];
    }
    
    // 💰 ALERTA: Saldo pendiente
    if ($saldoPendiente > 0) {
        $alerts[] = [
            'type' => 'info',
            'icon' => '💰',
            'title' => 'Saldo pendiente',
            'message' => "Hay un saldo pendiente de S/ " . number_format($saldoPendiente, 2),
            'action' => 'add_payment',
            'action_text' => 'Registrar pago',
        ];
    }
    
    // 🛒 ALERTA: Consumos pendientes
    $consumosPendientes = $booking->consumptions->where('status', 'pending')->count();
    if ($consumosPendientes > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => '🛒',
            'title' => 'Consumos pendientes',
            'message' => "Hay {$consumosPendientes} consumo(s) pendiente(s) de pago",
            'action' => 'review_consumptions',
            'action_text' => 'Revisar consumos',
        ];
    }
    
    return $alerts;
}
}