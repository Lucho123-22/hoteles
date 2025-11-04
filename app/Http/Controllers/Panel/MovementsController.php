<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Movement\StoreMovementRequest;
use App\Http\Requests\Movement\UpdateMovementRequest;
use App\Http\Resources\Movement\MovementResource;
use App\Models\Movement;
use App\Pipelines\Movement\MovementPipeline;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class MovementsController extends Controller{
    use ApiResponse, AuthorizesRequests;
    public function index(){
        try {
            Gate::authorize('viewAny', Movement::class);
            $query = Movement::with(['provider', 'subBranch', 'details'])
                ->where('sub_branch_id', Auth::user()->sub_branch_id);
            $movementType = request()->input('movement_type', 'ingreso');
            $query->where('movement_type', $movementType);
            $movements = app(MovementPipeline::class)->handle($query);
            $perPage = request()->input('per_page', 15);
            $movements = $movements->paginate($perPage);
            return MovementResource::collection($movements);
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudieron listar los movimientos.');
        }
    }
    public function store(StoreMovementRequest $request){
        try {
            $data = $request->validated();
            $data['sub_branch_id'] = Auth::user()->sub_branch_id;
            $data['created_by'] = Auth::id();
            $movement = Movement::create($data);
            return $this->created([
                'id' => $movement->id,
                'movement_type' => $movement->movement_type,
                'message' => 'Movimiento creado correctamente. Ahora puede agregar los detalles.'
            ], 'Movimiento creado correctamente.');
            
        } catch (Throwable $e) {
            return $this->exception($e, 'Error al crear el movimiento.');
        }
    }
    public function show(Movement $movement){
        Gate::authorize('view', $movement);
        return response()->json([
            'success' => true,
            'data' => new MovementResource($movement),
        ]);
    }
    public function update(UpdateMovementRequest $request, Movement $movement){
        Gate::authorize('update', $movement);
        $data = $request->validated();
        $data['updated_by'] = Auth::id();
        $data['sub_branch_id'] = Auth::user()->sub_branch_id;
        $movement->update($data);
        return $this->success(new MovementResource($movement), 'Movimiento actualizado correctamente.');
    }
    public function destroy(Movement $movement){
        Gate::authorize('delete', $movement);
        $movement->delete();
        return $this->success(null, 'Movimiento eliminado correctamente.');
    }
}
