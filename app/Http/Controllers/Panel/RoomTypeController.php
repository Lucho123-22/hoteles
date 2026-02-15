<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\StoreRoomTypeRequest;
use App\Http\Requests\RoomType\UpdateRoomTypeRequest;
use App\Http\Resources\Room\RoomTypeCollection;
use App\Http\Resources\Room\RoomTypeResource;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomTypeController extends Controller
{
    /**
     * Listar todos los tipos de habitación
     */
    public function index(Request $request)
    {
        $query = RoomType::query();

        // Filtros
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
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
        if ($request->boolean('with_rooms_count')) {
            $query->withCount('rooms');
        }

        if ($request->boolean('with_pricing_ranges_count')) {
            $query->withCount('pricingRanges');
        }

        $roomTypes = $query->get();

        return new RoomTypeCollection($roomTypes);
    }

    /**
     * Crear un nuevo tipo de habitación
     */
    public function store(StoreRoomTypeRequest $request): JsonResponse
    {
        $roomType = RoomType::create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return (new RoomTypeResource($roomType))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un tipo de habitación específico
     */
    public function show(Request $request, RoomType $roomType): RoomTypeResource
    {
        if ($request->boolean('with_rooms_count')) {
            $roomType->loadCount('rooms');
        }

        if ($request->boolean('with_pricing_ranges_count')) {
            $roomType->loadCount('pricingRanges');
        }

        return new RoomTypeResource($roomType);
    }

    /**
     * Actualizar un tipo de habitación
     */
    public function update(UpdateRoomTypeRequest $request, RoomType $roomType): JsonResponse
    {
        $roomType->update([
            ...$request->validated(),
            'updated_by' => Auth::id(),
        ]);

        return (new RoomTypeResource($roomType->fresh()))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Eliminar (soft delete) un tipo de habitación
     */
    public function destroy(RoomType $roomType): JsonResponse
    {
        // Verificar si tiene habitaciones asociadas
        if ($roomType->rooms()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este tipo de habitación porque tiene habitaciones asociadas',
            ], 422);
        }

        $roomType->update(['deleted_by' => Auth::id()]);
        $roomType->delete();

        return response()->json([
            'message' => 'Tipo de habitación eliminado correctamente',
        ], 200);
    }
}
