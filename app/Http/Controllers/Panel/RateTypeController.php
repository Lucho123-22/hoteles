<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\RateType\StoreRateTypeRequest;
use App\Http\Requests\RateType\UpdateRateTypeRequest;
use App\Http\Resources\RateType\RateTypeCollection;
use App\Http\Resources\RateType\RateTypeResource;
use App\Models\RateType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RateTypeController extends Controller{
    public function index(Request $request){
        $query = RateType::query();

        // Filtros
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('code')) {
            $query->where('code', strtoupper($request->code));
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('code', 'ilike', "%{$request->search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Cargar relaciones opcionales
        if ($request->boolean('with_pricing_ranges_count')) {
            $query->withCount('pricingRanges');
        }

        $rateTypes = $query->get();

        return new RateTypeCollection($rateTypes);
    }

    /**
     * Crear un nuevo tipo de tarifa
     */
    public function store(StoreRateTypeRequest $request): JsonResponse
    {
        $rateType = RateType::create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return (new RateTypeResource($rateType))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un tipo de tarifa específico
     */
    public function show(Request $request, RateType $rateType): RateTypeResource
    {
        if ($request->boolean('with_pricing_ranges_count')) {
            $rateType->loadCount('pricingRanges');
        }

        return new RateTypeResource($rateType);
    }

    /**
     * Actualizar un tipo de tarifa
     */
    public function update(UpdateRateTypeRequest $request, RateType $rateType): JsonResponse
    {
        $rateType->update([
            ...$request->validated(),
            'updated_by' => Auth::id(),
        ]);

        return (new RateTypeResource($rateType->fresh()))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Eliminar (soft delete) un tipo de tarifa
     */
    public function destroy(RateType $rateType): JsonResponse
    {
        $rateType->update(['deleted_by' => Auth::id()]);
        $rateType->delete();

        return response()->json([
            'message' => 'Tipo de tarifa eliminado correctamente',
        ], 200);
    }
}
