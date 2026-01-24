<?php

namespace App\Http\Controllers\Panel;

use App\Http\Requests\Cash\StoreCashRegisterRequest;
use App\Http\Resources\CashRegister\CashRegisterResource;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CashRegisterController extends Controller{
    public function index(Request $request){
        try {
            Gate::authorize('viewAny', CashRegister::class);
            $user = Auth::user();
            $cashRegisters = CashRegister::with([
                    'subBranch',
                    'currentSession.openedBy',  // Cambiar de openedByUser a openedBy
                    'sessions.openedBy',        // Cambiar de openedByUser a openedBy
                    'sessions.closedBy',        // Cambiar de closedByUser a closedBy
                ])
                ->where('sub_branch_id', $user->sub_branch_id)
                ->orderBy('created_at', 'asc')
                ->get();
            return response()->json([
                'success' => true,
                'message' => 'Listado de cajas obtenido correctamente',
                'data'    => CashRegisterResource::collection($cashRegisters),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error listing cash registers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de cajas registradoras'
            ], 500);
        }
    }
    public function storeMultiple(StoreCashRegisterRequest $request)
    {
        Gate::authorize('create', CashRegister::class);

        $user = Auth::user();
        $subBranchId = $user->sub_branch_id;
        $quantity = $request->quantity;
        try {
            DB::beginTransaction();
            $lastNumber = CashRegister::where('sub_branch_id', $subBranchId)
                ->selectRaw("
                    MAX(
                        CAST(
                            split_part(name, ' ', 2) AS INTEGER
                        )
                    ) as max_number
                ")
                ->value('max_number');
            $startNumber = ($lastNumber ?? 0) + 1;
            $created = [];
            for ($i = 0; $i < $quantity; $i++) {
                $cashRegister = CashRegister::create([
                    'sub_branch_id' => $subBranchId,
                    'name'          => 'Caja ' . ($startNumber + $i),
                    'status'        => 'cerrada',
                    'is_active'     => true,
                    'created_by'    => $user->id,
                ]);
                $created[] = $cashRegister;
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cajas creadas exitosamente',
                'data'    => CashRegisterResource::collection($created),
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating cash registers', [
                'error'   => $e->getMessage(),
                'user_id'=> $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear las cajas',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function show(string $id){
        try {
            $cashRegister = CashRegister::with(['subBranch', 'openedByUser', 'closedByUser'])
                ->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => new CashRegisterResource($cashRegister)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver esta caja'
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Caja registradora no encontrada'
            ], 404);
        } catch (Exception $e) {
            Log::error('Error showing cash register: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la caja registradora'
            ], 500);
        }
    }
    public function open(Request $request, string $id){
        try {
            $user = Auth::user();
            $request->validate([
                'opening_amount' => ['required', 'numeric', 'min:0'],
            ]);
            $hasOpenSession = CashRegisterSession::where('opened_by', $user->id)
                ->where('status', 'abierta')
                ->exists();
            if ($hasOpenSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una caja aperturada'
                ], 409);
            }
            $cashRegister = CashRegister::where('id', $id)
                ->where('sub_branch_id', $user->sub_branch_id)
                ->firstOrFail();
            if ($cashRegister->current_session_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta caja ya está siendo utilizada'
                ], 409);
            }
            DB::beginTransaction();
            $session = CashRegisterSession::create([
                'cash_register_id' => $cashRegister->id,
                'status'           => 'abierta',
                'opened_by'        => $user->id,
                'opening_amount'   => $request->opening_amount,
                'opened_at'        => Carbon::now(),
                'created_by'       => $user->id,
            ]);
            $cashRegister->update([
                'current_session_id' => $session->id,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Caja aperturada correctamente',
                'data' => [
                    'cash_register_id' => $cashRegister->id,
                    'session_id'       => $session->id,
                    'opened_at'        => $session->opened_at->format('d/m/Y h:i:s A'),
                    'opening_amount'   => $session->opening_amount
                ]
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al aperturar la caja'
            ], 500);
        }
    }
}