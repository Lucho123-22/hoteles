<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\BookingConsumption;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingConsumptionController extends Controller{
    public function index(Request $request){
        try {
            $query = BookingConsumption::with(['product', 'booking']);
            
            // Filtros
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
}
