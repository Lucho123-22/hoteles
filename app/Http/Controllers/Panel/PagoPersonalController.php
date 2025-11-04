<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagoPersonal\StorePagoPersonalRequest;
use App\Http\Requests\PagoPersonal\UpdatePagoPersonalRequest;
use App\Http\Resources\PagoPersonal\PagoPersonalResource;
use App\Models\PagoPersonal;
use App\Pipelines\PagosPersonal\PorEstado;
use App\Pipelines\PagosPersonal\PorPeriodo;
use App\Pipelines\PagosPersonal\PorSucursal;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PagoPersonalController extends Controller{
    public function index(Request $request){
        Gate::authorize('viewAny', PagoPersonal::class);
        $request->validate([
            'sub_branch_id' => 'nullable|exists:sub_branches,id',
            'estado' => 'nullable|in:pendiente,pagado,cancelado',
            'periodo' => 'nullable|date_format:Y-m'
        ]);
        $pagos = app(Pipeline::class)
            ->send(PagoPersonal::query()->with(['empleado', 'sucursal', 'registradoPor']))
            ->through([
                PorSucursal::class,
                PorEstado::class,
                PorPeriodo::class,
            ])
            ->thenReturn()
            ->latest('fecha_pago')
            ->paginate(15);
        return PagoPersonalResource::collection($pagos);
    }
    public function store(StorePagoPersonalRequest $request){
        try {
            Gate::authorize('create', PagoPersonal::class);
            DB::beginTransaction();
            $userAuth = Auth::user();
            $data = [
                'user_id' => $request->user_id,
                'sub_branch_id' => $userAuth->sub_branch_id,
                'monto' => $request->monto,
                'fecha_pago' => $request->fecha_pago,
                'periodo' => $request->periodo,
                'tipo_pago' => $request->tipo_pago,
                'metodo_pago' => $request->metodo_pago,
                'concepto' => $request->concepto,
                'estado' => $request->estado,
                'registrado_por' => $userAuth->id,
            ];
            if ($request->hasFile('comprobante')) {
                $file = $request->file('comprobante');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('comprobantes', $fileName, 'public');
                $data['comprobante'] = $path;
            }
            $pago = PagoPersonal::create($data);
            DB::commit();
            
            return response()->json([
                'message' => 'Pago registrado correctamente.',
                'data' => new PagoPersonalResource($pago)
            ], 201);
            
        } catch (Throwable $e) {
            DB::rollBack();
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            return response()->json([
                'message' => 'Error al registrar el pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($id){
        $pago = PagoPersonal::with(['empleado', 'sucursal', 'registradoPor'])->findOrFail($id);
        Gate::authorize('view', $pago);
        return response()->json([
            'data' => new PagoPersonalResource($pago)
        ]);
    }
    public function update(UpdatePagoPersonalRequest $request, $id){
        $pago = PagoPersonal::findOrFail($id);
        Gate::authorize('update', $pago);
        try {
            DB::beginTransaction();
            $data = $request->validated();
            if ($request->hasFile('comprobante')) {
                if ($pago->comprobante) {
                    Storage::disk('public')->delete($pago->comprobante);
                }
                $file = $request->file('comprobante');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('comprobantes', $fileName, 'public');
                $data['comprobante'] = $path;
            }
            $pago->update($data);
            DB::commit();
            return response()->json([
                'message' => 'Pago actualizado correctamente.',
                'data' => new PagoPersonalResource($pago->fresh())
            ]);
            
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id){
        $pago = PagoPersonal::findOrFail($id);
        Gate::authorize('delete', $pago);
        try {
            DB::beginTransaction();
            if ($pago->comprobante) {
                Storage::disk('public')->delete($pago->comprobante);
            }
            $pago->delete();
            DB::commit();
            return response()->json([
                'message' => 'Pago eliminado correctamente.'
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function historial(){
        Gate::authorize('viewReport', PagoPersonal::class);
        $pagos = PagoPersonal::with(['empleado', 'sucursal'])
            ->where('sub_branch_id', Auth::user()->sub_branch_id)
            ->latest('fecha_pago')
            ->paginate(20);
            
        return PagoPersonalResource::collection($pagos);
    }
    public function approve($id){
        $pago = PagoPersonal::findOrFail($id);
        Gate::authorize('approve', $pago);
        try {
            DB::beginTransaction();
            $pago->update([
                'estado' => 'aprobado',
                'fecha_aprobacion' => now()
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Pago aprobado correctamente.',
                'data' => new PagoPersonalResource($pago->fresh())
            ]);
            
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al aprobar el pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function cancel($id){
        $pago = PagoPersonal::findOrFail($id);
        Gate::authorize('cancel', $pago);
        try {
            DB::beginTransaction();
            $pago->update([
                'estado' => 'anulado',
                'fecha_anulacion' => now()
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Pago anulado correctamente.',
                'data' => new PagoPersonalResource($pago->fresh())
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular el pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function export(){
        Gate::authorize('export', PagoPersonal::class);
        return response()->json([
            'message' => 'Exportación de pagos'
        ]);
    }
}