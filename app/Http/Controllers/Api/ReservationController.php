<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RateType;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * Crear una reserva futura
     * Ejemplo: "Quiero reservar la habitación 101 para el viernes a las 2pm"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'customer_id' => 'required|exists:customers,id',
            'rate_type_id' => 'required|exists:rate_types,id',
            'check_in' => 'required|date|after:now', // "2026-01-17 14:00:00"
            'check_out' => 'required|date|after:check_in',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $room = Room::with(['floor.subBranch.branch', 'roomType'])->findOrFail($validated['room_id']);
            $rateType = RateType::findOrFail($validated['rate_type_id']);

            // Verificar que no haya otra reserva activa en esas fechas
            $conflicto = Booking::where('room_id', $room->id)
                ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                        ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('check_in', '<=', $validated['check_in'])
                              ->where('check_out', '>=', $validated['check_out']);
                        });
                })
                ->first();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'La habitación ya tiene una reserva en esas fechas',
                    'conflicto' => [
                        'codigo' => $conflicto->booking_code,
                        'check_in' => $conflicto->check_in,
                        'check_out' => $conflicto->check_out,
                        'status' => $conflicto->status,
                    ]
                ], 422);
            }

            // Calcular totales
            $checkIn = Carbon::parse($validated['check_in']);
            $checkOut = Carbon::parse($validated['check_out']);
            $totalHours = $rateType->calculateTotalHours($checkIn, $checkOut);
            
            $branch = $room->floor->subBranch->branch;
            $ratePerUnit = $room->roomType->getPriceForBranch($branch, $rateType);
            $roomSubtotal = $ratePerUnit * $totalHours;

            // Crear la reserva
            $booking = Booking::create([
                'room_id' => $room->id,
                'customers_id' => $validated['customer_id'],
                'rate_type_id' => $rateType->id,
                'sub_branch_id' => $room->floor->sub_branch_id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'total_hours' => $totalHours,
                'rate_per_unit' => $ratePerUnit,
                'rate_per_hour' => $ratePerUnit,
                'room_subtotal' => $roomSubtotal,
                'products_subtotal' => 0,
                'subtotal' => $roomSubtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $roomSubtotal,
                'paid_amount' => 0,
                'status' => Booking::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reserva creada exitosamente',
                'data' => [
                    'id' => $booking->id,
                    'codigo' => $booking->booking_code,
                    'habitacion' => $room->room_number,
                    'cliente' => $booking->customer->name,
                    'check_in' => $booking->check_in->format('d/m/Y H:i'),
                    'check_out' => $booking->check_out->format('d/m/Y H:i'),
                    'total_horas' => $booking->total_hours,
                    'precio_total' => $booking->total_amount,
                    'status' => $booking->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar reservas futuras
     */
    public function index(Request $request)
    {
        $query = Booking::with(['room', 'customer', 'rateType'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_in', '>', now())
            ->orderBy('check_in', 'asc');

        // Filtro opcional por sucursal
        if ($request->has('sub_branch_id')) {
            $query->where('sub_branch_id', $request->sub_branch_id);
        }

        // Filtro opcional por fecha
        if ($request->has('fecha')) {
            $fecha = Carbon::parse($request->fecha);
            $query->whereDate('check_in', $fecha);
        }

        $reservas = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reservas->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'codigo' => $booking->booking_code,
                    'habitacion' => $booking->room->room_number,
                    'cliente' => $booking->customer->name,
                    'check_in' => $booking->check_in->format('d/m/Y H:i'),
                    'check_out' => $booking->check_out->format('d/m/Y H:i'),
                    'total' => $booking->total_amount,
                    'status' => $booking->status,
                ];
            }),
            'pagination' => [
                'total' => $reservas->total(),
                'current_page' => $reservas->currentPage(),
                'last_page' => $reservas->lastPage(),
            ]
        ]);
    }

    /**
     * Verificar disponibilidad de una habitación en una fecha/hora específica
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $disponible = !Booking::where('room_id', $validated['room_id'])
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                    ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('check_in', '<=', $validated['check_in'])
                          ->where('check_out', '>=', $validated['check_out']);
                    });
            })
            ->exists();

        return response()->json([
            'success' => true,
            'disponible' => $disponible,
            'mensaje' => $disponible 
                ? 'La habitación está disponible' 
                : 'La habitación ya tiene una reserva en ese horario'
        ]);
    }

    /**
     * Cancelar una reserva
     */
    public function cancel(Request $request, $id)
    {
        $validated = $request->validate([
            'motivo' => 'required|string',
        ]);

        try {
            $booking = Booking::findOrFail($id);

            if (!in_array($booking->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cancelar reservas pendientes o confirmadas'
                ], 422);
            }

            $booking->cancel($validated['motivo'], Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Reserva cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver detalles de una reserva
     */
    public function show($id)
    {
        $booking = Booking::with(['room', 'customer', 'rateType'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $booking->id,
                'codigo' => $booking->booking_code,
                'habitacion' => [
                    'numero' => $booking->room->room_number,
                    'tipo' => $booking->room->roomType->name,
                ],
                'cliente' => [
                    'nombre' => $booking->customer->name,
                    'documento' => $booking->customer->document_number ?? 'N/A',
                ],
                'check_in' => $booking->check_in->format('d/m/Y H:i'),
                'check_out' => $booking->check_out->format('d/m/Y H:i'),
                'total_horas' => $booking->total_hours,
                'precio_por_hora' => $booking->rate_per_hour,
                'total' => $booking->total_amount,
                'pagado' => $booking->paid_amount,
                'saldo' => $booking->total_amount - $booking->paid_amount,
                'status' => $booking->status,
                'notas' => $booking->notes,
                'creado_el' => $booking->created_at->format('d/m/Y H:i'),
            ]
        ]);
    }
}