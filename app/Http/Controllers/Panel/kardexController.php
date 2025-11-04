<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Kardex\KardexResource;
use App\Http\Resources\Kardex\KardexValorizadoResource;
use App\Http\Resources\SubBranch\SubBranchProductResource;
use App\Models\Kardex;
use App\Models\SubBranchProduct;
use App\Pipelines\Floor\Filters\FilterByActive;
use App\Pipelines\Floor\Filters\FilterBySubBranch;
use App\Pipelines\Inventory\FilterBySearch;
use App\Pipelines\Inventory\OrderByStock;
use App\Pipelines\Kardex\FilterByDateRange;
use App\Pipelines\Kardex\FilterByMovementCategory;
use App\Pipelines\Kardex\FilterByMovementType;
use App\Pipelines\Kardex\FilterByProduct;
use App\Pipelines\Kardex\OrderByLatest;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class kardexController extends Controller{
    use ApiResponse, AuthorizesRequests;
    public function index(Request $request){
        $perPage = $request->input('per_page', 15);
        if (!$request->filled(['product_id', 'fecha_inicio', 'fecha_fin', 'sub_branch_id'])) {
            return KardexResource::collection(collect([]));
        }
        $query = app(Pipeline::class)
            ->send(Kardex::with(['product', 'subBranch', 'movementDetail', 'sale']))
            ->through([
                FilterByProduct::class,
                FilterByDateRange::class,
                FilterBySubBranch::class,
                //OrderByLatest::class,
            ])
            ->thenReturn();
        $kardex = $query->paginate($perPage);
        return KardexResource::collection($kardex);
    }
    public function indexGeneral(Request $request){
        $perPage = $request->input('per_page', 15);
        $query = app(Pipeline::class)
            ->send(Kardex::with(['product', 'subBranch']))
            ->through([
                FilterByProduct::class,
                FilterBySubBranch::class,
                FilterByDateRange::class,
                FilterByMovementType::class,
                FilterByMovementCategory::class,
                //OrderByLatest::class,
            ])
            ->thenReturn();
        $kardexEntries = $query->paginate($perPage);
        return KardexResource::collection($kardexEntries);
    }
    public function indexKardexValorizado(Request $request){
        $perPage = $request->input('per_page', 15);
        if (!$request->filled(['product_id', 'fecha_inicio', 'fecha_fin', 'sub_branch_id'])) {
            return $this->successResponse([], 'Debe enviar al menos un filtro para listar el kardex valorizado');
        }
        $query = app(Pipeline::class)
            ->send(Kardex::with(['product', 'subBranch']))
            ->through([
                FilterByProduct::class,
                FilterBySubBranch::class,
                FilterByDateRange::class,
                FilterByMovementType::class,
            ])
            ->thenReturn();
        $kardex = $query->paginate($perPage);
        return KardexValorizadoResource::collection($kardex);
    }
    public function inventario(Request $request, $subBranchId){
        try {
            $perPage = $request->input('per_page', 15);
            $query = SubBranchProduct::with(['product.category'])
                ->where('sub_branch_id', $subBranchId);
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('codigo', 'LIKE', "%{$search}%");
                });
            }
            if ($request->filled('category')) {
                $query->whereHas('product.category', function ($q) use ($request) {
                    $q->where('nombre', $request->input('category'));
                });
            }
            if ($request->filled('has_stock') && filter_var($request->input('has_stock'), FILTER_VALIDATE_BOOLEAN)) {
                $query->where('current_stock', '>', DB::raw('min_stock'));
            } elseif ($request->filled('is_low_stock') && filter_var($request->input('is_low_stock'), FILTER_VALIDATE_BOOLEAN)) {
                $query->where('current_stock', '>', 0)
                    ->where('current_stock', '<=', DB::raw('min_stock'));
            } elseif ($request->filled('no_stock') && filter_var($request->input('no_stock'), FILTER_VALIDATE_BOOLEAN)) {
                $query->where('current_stock', 0);
            }
            if ($request->filled('is_active')) {
                $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $isActive);
            }
            $sortBy = $request->input('sort_by', 'current_stock');
            $sortOrder = $request->input('sort_order', 'desc');
            if (in_array($sortBy, ['current_stock', 'min_stock', 'max_stock', 'product_name'])) {
                if ($sortBy === 'product_name') {
                    $query->join('products', 'sub_branch_products.product_id', '=', 'products.id')
                        ->orderBy('products.nombre', $sortOrder)
                        ->select('sub_branch_products.*');
                } else {
                    $query->orderBy($sortBy, $sortOrder);
                }
            } else {
                $query->orderBy('current_stock', 'desc');
            }
            $productos = $query->paginate($perPage);
            $resumen = SubBranchProduct::where('sub_branch_id', $subBranchId)
                ->selectRaw('
                    COUNT(*) as total_productos,
                    SUM(CASE WHEN current_stock > min_stock THEN 1 ELSE 0 END) as stock_disponible,
                    SUM(CASE WHEN current_stock > 0 AND current_stock <= min_stock THEN 1 ELSE 0 END) as stock_bajo,
                    SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as sin_stock,
                    SUM(current_stock) as stock_total,
                    AVG(CASE WHEN max_stock > 0 THEN (current_stock * 100.0 / max_stock) ELSE 0 END) as porcentaje_ocupacion
                ')
                ->first();
            return response()->json([
                'message' => 'Inventario listado correctamente.',
                'resumen' => [
                    'total_productos' => (int) $resumen->total_productos,
                    'stock_disponible' => (int) $resumen->stock_disponible,
                    'stock_bajo' => (int) $resumen->stock_bajo,
                    'sin_stock' => (int) $resumen->sin_stock,
                    'stock_total' => (int) $resumen->stock_total,
                    'porcentaje_ocupacion' => round($resumen->porcentaje_ocupacion, 2)
                ],
                'data' => SubBranchProductResource::collection($productos),
                'pagination' => [
                    'current_page' => $productos->currentPage(),
                    'per_page' => $productos->perPage(),
                    'total' => $productos->total(),
                    'last_page' => $productos->lastPage(),
                    'from' => $productos->firstItem(),
                    'to' => $productos->lastItem(),
                ]
            ], 200);

        } catch (Throwable $th) {
            Log::error('Error al listar inventario: ' . $th->getMessage(), [
                'sub_branch_id' => $subBranchId,
                'request_data' => $request->all(),
                'trace' => $th->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al listar inventario.',
                'error' => config('app.debug') ? $th->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}
