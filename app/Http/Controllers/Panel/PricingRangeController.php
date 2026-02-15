<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\PricingRange\StorePricingRangeRequest;
use App\Http\Requests\PricingRange\UpdatePricingRangeRequest;
use App\Http\Resources\PricingRange\PricingRangeCollection;
use App\Http\Resources\PricingRange\PricingRangeResource;
use App\Models\PricingRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PricingRangeController extends Controller
{
    /**
     * Listar todos los rangos de precio
     */
    public function index(Request $request)
    {
        $query = PricingRange::with(['roomType', 'rateType']);

        // Filtros obligatorios
        if ($request->filled('sub_branch_id')) {
            $query->where('sub_branch_id', $request->sub_branch_id);
        }

        // Filtros opcionales
        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        if ($request->filled('rate_type_id')) {
            $query->where('rate_type_id', $request->rate_type_id);
        }

        if ($request->filled('rate_type_code')) {
            $query->whereHas('rateType', function ($q) use ($request) {
                $q->where('code', strtoupper($request->rate_type_code));
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filtrar por vigencia
        if ($request->boolean('only_effective')) {
            $query->effectiveNow();
        }

        // Filtrar por rango de minutos
        if ($request->filled('minutes')) {
            $query->forMinutes($request->integer('minutes'));
        }

        // Ordenamiento mejorado
        $sortBy = $request->get('sort_by', 'time_from_minutes');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'time_from_minutes') {
            // Ordenar primero tarifas fijas (null), luego por tiempo
            $query->orderByRaw('time_from_minutes IS NULL DESC, time_from_minutes ' . $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $pricingRanges = $query->get();

        return new PricingRangeCollection($pricingRanges);
    }

    /**
     * Crear un nuevo rango de precio
     */
    public function store(StorePricingRangeRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $pricingRange = PricingRange::create([
                ...$request->validated(),
                'created_by' => Auth::id(),
            ]);

            $pricingRange->load(['roomType', 'rateType', 'subBranch']);

            DB::commit();

            return (new PricingRangeResource($pricingRange))
                ->response()
                ->setStatusCode(201);
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al crear el rango de precio',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Mostrar un rango de precio específico
     */
    public function show(PricingRange $pricingRange): PricingRangeResource
    {
        $pricingRange->load(['roomType', 'rateType', 'subBranch', 'createdBy', 'updatedBy']);

        return new PricingRangeResource($pricingRange);
    }

    /**
     * Actualizar un rango de precio
     */
    public function update(UpdatePricingRangeRequest $request, PricingRange $pricingRange): JsonResponse
    {
        try {
            DB::beginTransaction();

            $pricingRange->update([
                ...$request->validated(),
                'updated_by' => Auth::id(),
            ]);

            $pricingRange->load(['roomType', 'rateType', 'subBranch']);

            DB::commit();

            return (new PricingRangeResource($pricingRange->fresh()))
                ->response()
                ->setStatusCode(200);
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al actualizar el rango de precio',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Buscar precio para condiciones específicas
     */
    public function findPrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sub_branch_id' => ['required', 'uuid', 'exists:sub_branches,id'],
            'room_type_id' => ['required', 'uuid', 'exists:room_types,id'],
            'rate_type_id' => ['required', 'uuid', 'exists:rate_types,id'],
            'minutes' => ['nullable', 'integer', 'min:0'],
            'date' => ['nullable', 'date'],
        ]);

        $price = PricingRange::findPrice(
            subBranchId: $validated['sub_branch_id'],
            roomTypeId: $validated['room_type_id'],
            rateTypeId: $validated['rate_type_id'],
            minutes: $validated['minutes'] ?? null,
            date: $validated['date'] ?? null
        );

        if (!$price) {
            return response()->json([
                'message' => 'No se encontró un precio para las condiciones especificadas',
                'data' => null
            ], 404);
        }

        return (new PricingRangeResource($price->load(['roomType', 'rateType'])))
            ->response();
    }

    /**
     * Obtener rangos disponibles
     */
    public function availableRanges(Request $request): PricingRangeCollection
    {
        $validated = $request->validate([
            'sub_branch_id' => ['required', 'uuid', 'exists:sub_branches,id'],
            'room_type_id' => ['required', 'uuid', 'exists:room_types,id'],
            'rate_type_code' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
        ]);

        $ranges = PricingRange::getAvailableRanges(
            subBranchId: $validated['sub_branch_id'],
            roomTypeId: $validated['room_type_id'],
            rateTypeCode: $validated['rate_type_code'] ?? null,
            date: $validated['date'] ?? null
        );

        return new PricingRangeCollection($ranges);
    }

    /**
     * Eliminar (soft delete) un rango de precio
     */
    public function destroy(PricingRange $pricingRange): JsonResponse
    {
        try {
            DB::beginTransaction();

            $pricingRange->update(['deleted_by' => Auth::id()]);
            $pricingRange->delete();

            DB::commit();

            return response()->json([
                'message' => 'Rango de precio eliminado correctamente',
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al eliminar el rango de precio',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Validar configuración de precios (útil antes de crear/actualizar)
     */
    public function validateConfiguration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sub_branch_id' => ['required', 'uuid', 'exists:sub_branches,id'],
            'room_type_id' => ['required', 'uuid', 'exists:room_types,id'],
            'rate_type_id' => ['required', 'uuid', 'exists:rate_types,id'],
            'time_from_minutes' => ['nullable', 'integer', 'min:0'],
            'time_to_minutes' => ['nullable', 'integer', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date'],
            'exclude_id' => ['nullable', 'uuid'],
        ]);

        $hasOverlap = PricingRange::hasOverlap(
            subBranchId: $validated['sub_branch_id'],
            roomTypeId: $validated['room_type_id'],
            rateTypeId: $validated['rate_type_id'],
            timeFrom: $validated['time_from_minutes'] ?? null,
            timeTo: $validated['time_to_minutes'] ?? null,
            effectiveFrom: $validated['effective_from'],
            effectiveTo: $validated['effective_to'] ?? null,
            excludeId: $validated['exclude_id'] ?? null
        );

        return response()->json([
            'valid' => !$hasOverlap,
            'message' => $hasOverlap
                ? 'Existe un solapamiento con otra configuración'
                : 'La configuración es válida'
        ]);
    }
}
