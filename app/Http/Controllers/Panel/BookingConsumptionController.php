<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingConsumption;
use App\Models\Kardex;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use TheSeer\Tokenizer\Exception;

class BookingConsumptionController extends Controller{
    public function index(Request $request){
        try {
            $query = BookingConsumption::with(['product', 'booking']);
            
            if ($request->has('month') && $request->has('year')) {
                $startDate = Carbon::create($request->year, $request->month, 1)->startOfMonth();
                $endDate = Carbon::create($request->year, $request->month, 1)->endOfMonth();
                $query->whereBetween('consumed_at', [$startDate, $endDate]);
            }
            
            if ($request->has('with_product')) {
                $query->with('product');
            }
            
            if ($request->has('with_booking')) {
                $query->with('booking');
            }
            
            $perPage = $request->input('per_page', 15);
            $consumos = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $consumos->items(),
                'meta' => [
                    'current_page' => $consumos->currentPage(),
                    'per_page' => $consumos->perPage(),
                    'total' => $consumos->total(),
                    'last_page' => $consumos->lastPage(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cargando consumos: ' . $e->getMessage()
            ], 500);
        }
    }
    public function store(Request $request){
        $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'product_id' => 'required|uuid|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);
        try {
            DB::beginTransaction();
            $consumption = BookingConsumption::addProducto(
                $request->booking_id,
                $request->product_id,
                $request->quantity,
                $request->notes
            );
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Producto agregado exitosamente',
                'data' => $consumption->load(['product', 'booking'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al agregar producto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function update(Request $request, $id){
        $request->validate([
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);
        try {
            DB::beginTransaction();
            $consumption = BookingConsumption::findOrFail($id);
            $consumption->updateProducto(
                $request->quantity,
                $request->notes
            );
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => $consumption->fresh()->load(['product', 'booking'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function destroy($id){
        try {
            DB::beginTransaction();
            $consumption = BookingConsumption::findOrFail($id);
            $consumption->deleteProducto();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar producto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function addConsumptions(Request $request, $bookingId)
    {
        try {
            DB::beginTransaction();
            
            $validated = $request->validate([
                'consumptions' => 'required|array|min:1',
                'consumptions.*.product_id' => 'required|exists:products,id',
                'consumptions.*.quantity' => 'required|numeric|min:0.01',
                'consumptions.*.unit_price' => 'required|numeric|min:0',
                'consumptions.*.notes' => 'nullable|string|max:500',
            ]);
            
            $booking = Booking::with(['room', 'customer', 'consumptions'])
                ->findOrFail($bookingId);
            
            // Validar que la reserva esté activa
            if (!in_array($booking->status, [
                Booking::STATUS_CONFIRMED, 
                Booking::STATUS_CHECKED_IN
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden agregar productos a una reserva que no está activa'
                ], 422);
            }
            
            $addedConsumptions = [];
            $productsSubtotalAdded = 0;
            $kardexMovements = [];
            
            foreach ($validated['consumptions'] as $consumption) {
                $product = Product::findOrFail($consumption['product_id']);
                
                // ============================================================
                // VALIDAR STOCK DISPONIBLE
                // ============================================================
                if ($product->track_inventory && $product->stock < $consumption['quantity']) {
                    throw new Exception(
                        "Stock insuficiente para {$product->name}. " .
                        "Disponible: {$product->stock}, Solicitado: {$consumption['quantity']}"
                    );
                }
                
                $totalPrice = $consumption['quantity'] * $consumption['unit_price'];
                
                // ============================================================
                // CREAR CONSUMO EN ESTADO PENDING
                // ============================================================
                $newConsumption = $booking->consumptions()->create([
                    'id' => Str::uuid(),
                    'product_id' => $consumption['product_id'],
                    'quantity' => $consumption['quantity'],
                    'unit_price' => $consumption['unit_price'],
                    'total_price' => $totalPrice,
                    'status' => 'pending', // ← Estado pendiente
                    'consumed_at' => now(),
                    'notes' => $consumption['notes'] ?? "Consumo adicional - Hab. {$booking->room->room_number}",
                    'created_by' => Auth::id(),
                ]);
                
                // ============================================================
                // REGISTRAR MOVIMIENTO EN KARDEX (SALIDA)
                // ============================================================
                if ($product->track_inventory) {
                    $stockBefore = $product->stock;
                    $stockAfter = $stockBefore - $consumption['quantity'];
                    
                    $kardexMovement = Kardex::create([
                        'id' => Str::uuid(),
                        'product_id' => $product->id,
                        'warehouse_id' => $product->warehouse_id ?? Auth::user()->sub_branch->default_warehouse_id,
                        'movement_type' => 'output', // Salida de inventario
                        'transaction_type' => 'consumption', // Tipo: consumo
                        'reference_type' => 'booking_consumption', // Referencia al consumo
                        'reference_id' => $newConsumption->id,
                        'quantity' => $consumption['quantity'],
                        'unit_cost' => $product->cost_price ?? 0,
                        'total_cost' => ($product->cost_price ?? 0) * $consumption['quantity'],
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'movement_date' => now(),
                        'notes' => "Consumo Hab. {$booking->room->room_number} - Booking #{$booking->booking_code} - {$booking->customer->name}",
                        'created_by' => Auth::id(),
                    ]);
                    
                    // ============================================================
                    // ACTUALIZAR STOCK DEL PRODUCTO
                    // ============================================================
                    $product->decrement('stock', $consumption['quantity']);
                    
                    $kardexMovements[] = $kardexMovement;
                }
                
                $addedConsumptions[] = $newConsumption->load('product');
                $productsSubtotalAdded += $totalPrice;
            }
            
            // ============================================================
            // ACTUALIZAR TOTALES DE LA RESERVA
            // ============================================================
            $booking->products_subtotal += $productsSubtotalAdded;
            $booking->subtotal = $booking->room_subtotal + $booking->products_subtotal;
            $booking->total_amount = $booking->subtotal - $booking->discount_amount + $booking->tax_amount;
            $booking->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => '✅ Productos agregados correctamente (pendientes de pago)',
                'data' => [
                    'consumptions_added' => $addedConsumptions,
                    'kardex_movements' => count($kardexMovements),
                    'products_subtotal_added' => $productsSubtotalAdded,
                    'booking_summary' => [
                        'booking_code' => $booking->booking_code,
                        'room' => $booking->room->room_number,
                        'customer' => $booking->customer->name,
                        'room_subtotal' => $booking->room_subtotal,
                        'products_subtotal' => $booking->products_subtotal,
                        'subtotal' => $booking->subtotal,
                        'tax_amount' => $booking->tax_amount,
                        'discount_amount' => $booking->discount_amount,
                        'total_amount' => $booking->total_amount,
                        'paid_amount' => $booking->paid_amount,
                        'balance' => $booking->balance, // Lo que falta por pagar
                    ],
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al agregar consumos:', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}
