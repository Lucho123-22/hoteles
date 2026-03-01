<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Floor\{
    IndexFloorRequest,
    CreateFloorRequest,
    UpdateFloorRequest
};
use App\Http\Resources\Floor\FloorResource;
use App\Http\Resources\FloorRoom\FloorRoomResource;
use App\Models\Floor;
use App\Services\FloorService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Throwable;

class FloorController extends Controller{
    use ApiResponse, AuthorizesRequests;
    protected $floorService;
    public function __construct(FloorService $floorService){
        
        $this->authorizeResource(Floor::class, 'floor');
        $this->floorService = $floorService;
    }
    public function index(IndexFloorRequest $request){
        try {
            $filters = $request->validated();
            $perPage = $filters['per_page'] ?? 15;
            $query = $this->floorService->search($filters);
            $floors = $query->paginate($perPage);
            return FloorResource::collection($floors);
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudieron listar los pisos.');
        }
    }
    public function show(Floor $floor){
        try {
            $floor->load([
                'subBranch',
                'rooms' => function ($query) {
                    $query->with('roomType')->orderBy('room_number');
                }
            ]);
            return new FloorResource($floor);
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudo obtener el piso.');
        }
    }
    public function store(CreateFloorRequest $request){
        try {
            $validatedData = $request->validated();
            $validatedData['is_active'] = $validatedData['is_active'] ?? true;
            $floor = Floor::create($validatedData);
            $floor->load('subBranch');
            
            return response()->json([
                'data' => new FloorResource($floor),
                'message' => 'Piso creado correctamente.'
            ], 201);
            
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudo crear el piso.');
        }
    }
    public function update(Floor $floor, UpdateFloorRequest $request){
        try {
            $floor->update($request->validated());
            $floor->load('subBranch');
            return $this->ok(
                new FloorResource($floor),
                'Piso actualizado correctamente.'
            );
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudo actualizar el piso.');
        }
    }
    public function destroy(Floor $floor){
        try {
            if ($floor->rooms()->where('is_active', true)->exists()) {
                return $this->error('No se puede eliminar un piso que tiene habitaciones activas.');
            }
            $floor->delete();
            return $this->ok(null, 'Piso eliminado correctamente.');
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudo eliminar el piso.');
        }
    }
    public function bySubBranch($subBranchId, IndexFloorRequest $request){
        try {
            $filters = $request->validated();
            $floors = $this->floorService
                        ->getBySubBranchWithRoomCounts($subBranchId, $filters)
                        ->orderBy('floor_number')
                        ->get();
            return FloorResource::collection($floors);
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudieron obtener los pisos de la sub sucursal.');
        }
    }
    public function withRoomCounts(IndexFloorRequest $request){
        try {
            $filters = $request->validated();
            $perPage = $filters['per_page'] ?? 15;
            $query = $this->floorService->searchWithRoomCounts($filters);
            $floors = $query->paginate($perPage);
            return FloorResource::collection($floors);
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudieron obtener los pisos con conteos de habitaciones.');
        }
    }

    public function floorRoom(){
        $user = Auth::user();
        
        if (!$user->sub_branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene una sub-sucursal asignada.',
                'data' => []
            ], 404);
        }

        $floors = Floor::with([
            'subBranch.timeSettings',
            'subBranch.penaltySettings',
            'rooms.roomType',
            'rooms.bookings' => function($query) {
                $query->whereIn('status', ['checked_in'])
                    ->with(['customer', 'rateType'])
                    ->latest('check_in');
            },
        ])
        ->where('sub_branch_id', $user->sub_branch_id)
        ->active()
        ->get();
        return FloorRoomResource::collection($floors);
    }
}