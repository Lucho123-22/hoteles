<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashRegisterSession;
use App\Models\CashRegisterSessionPaymentMethod;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashRegisterClosingController extends Controller
{
    /**
     * Cerrar la sesión activa del usuario autenticado
     * POST /cash-register-sessions/close
     */
    public function close(Request $request)
    {
        try {
            $user = Auth::user();

            // ============================================================
            // OBTENER SESIÓN ACTIVA DEL USUARIO AUTENTICADO
            // ============================================================
            $session = CashRegisterSession::where('opened_by', $user->id)
                ->where('status', 'abierta')
                ->with('cashRegister')
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes una sesión de caja abierta'
                ], 404);
            }

            // Validar request
            $validated = $request->validate([
                'counted_amounts' => 'required|array|min:1',
                'counted_amounts.*.payment_method_id' => 'required|uuid|exists:payment_methods,id',
                'counted_amounts.*.counted_amount' => 'required|numeric|min:0',
            ], [
                'counted_amounts.required' => 'Debe ingresar los montos contados',
                'counted_amounts.*.payment_method_id.required' => 'El método de pago es obligatorio',
                'counted_amounts.*.counted_amount.required' => 'El monto contado es obligatorio',
                'counted_amounts.*.counted_amount.min' => 'El monto contado no puede ser negativo',
            ]);

            DB::beginTransaction();

            // ============================================================
            // CALCULAR TOTALES DEL SISTEMA POR MÉTODO DE PAGO
            // ============================================================
            $systemPayments = Payment::where('cash_register_id', $session->cash_register_id)
                ->whereBetween('payment_date', [
                    $session->opened_at,
                    now()
                ])
                ->where('status', Payment::STATUS_COMPLETED)
                ->with('paymentMethod')
                ->get()
                ->groupBy('payment_method_id');

            $systemTotalAmount = 0;
            $countedTotalAmount = 0;
            $paymentMethodDetails = [];

            // Procesar cada método de pago que tiene transacciones
            foreach ($systemPayments as $methodId => $payments) {
                $systemAmount = $payments->sum('amount');
                $systemTotalAmount += $systemAmount;

                // Buscar el monto contado por el usuario para este método
                $countedData = collect($validated['counted_amounts'])
                    ->firstWhere('payment_method_id', $methodId);

                $countedAmount = $countedData['counted_amount'] ?? 0;
                $countedTotalAmount += $countedAmount;

                $differenceAmount = $countedAmount - $systemAmount;

                // Guardar detalle por método de pago
                CashRegisterSessionPaymentMethod::create([
                    'cash_register_session_id' => $session->id,
                    'payment_method_id' => $methodId,
                    'system_amount' => $systemAmount,
                    'counted_amount' => $countedAmount,
                    'difference_amount' => $differenceAmount,
                ]);

                $paymentMethodDetails[] = [
                    'payment_method' => $payments->first()->paymentMethod->name ?? 'Desconocido',
                    'system_amount' => round($systemAmount, 2),
                    'counted_amount' => round($countedAmount, 2),
                    'difference' => round($differenceAmount, 2),
                    'transaction_count' => $payments->count(),
                ];
            }

            // ============================================================
            // VERIFICAR MÉTODOS QUE EL USUARIO CONTÓ PERO NO HAY EN SISTEMA
            // ============================================================
            foreach ($validated['counted_amounts'] as $countedData) {
                $methodId = $countedData['payment_method_id'];
                
                if (!$systemPayments->has($methodId)) {
                    $countedAmount = $countedData['counted_amount'];
                    $countedTotalAmount += $countedAmount;

                    CashRegisterSessionPaymentMethod::create([
                        'cash_register_session_id' => $session->id,
                        'payment_method_id' => $methodId,
                        'system_amount' => 0,
                        'counted_amount' => $countedAmount,
                        'difference_amount' => $countedAmount,
                    ]);

                    $paymentMethod = PaymentMethod::find($methodId);
                    $paymentMethodDetails[] = [
                        'payment_method' => $paymentMethod->name ?? 'Desconocido',
                        'system_amount' => 0,
                        'counted_amount' => round($countedAmount, 2),
                        'difference' => round($countedAmount, 2),
                        'transaction_count' => 0,
                    ];
                }
            }

            // ============================================================
            // CALCULAR DIFERENCIA TOTAL
            // ============================================================
            $totalDifference = $countedTotalAmount - $systemTotalAmount;

            // ============================================================
            // ACTUALIZAR SESIÓN
            // ============================================================
            $session->update([
                'status' => 'cerrada',
                'closed_by' => $user->id,
                'closed_at' => now(),
                'system_total_amount' => $systemTotalAmount,
                'counted_total_amount' => $countedTotalAmount,
                'difference_amount' => $totalDifference,
                'updated_by' => $user->id,
            ]);

            // ============================================================
            // LIBERAR CAJA REGISTRADORA
            // ============================================================
            $session->cashRegister->update([
                'current_session_id' => null,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Caja cerrada exitosamente',
                'data' => [
                    'session' => [
                        'id' => $session->id,
                        'cash_register_name' => $session->cashRegister->name,
                        'opened_at' => $session->opened_at->format('Y-m-d H:i:s'),
                        'closed_at' => $session->closed_at->format('Y-m-d H:i:s'),
                        'duration' => $session->opened_at->diffForHumans($session->closed_at, true),
                        'opened_by' => $session->openedBy->name,
                        'closed_by' => $session->closedBy->name,
                    ],
                    'summary' => [
                        'opening_amount' => round($session->opening_amount, 2),
                        'system_total' => round($systemTotalAmount, 2),
                        'counted_total' => round($countedTotalAmount, 2),
                        'difference' => round($totalDifference, 2),
                        'status' => $totalDifference == 0 ? 'CUADRA ✅' : ($totalDifference > 0 ? 'SOBRANTE 📈' : 'FALTANTE 📉'),
                    ],
                    'payment_methods' => $paymentMethodDetails,
                    'total_transactions' => Payment::where('cash_register_id', $session->cash_register_id)
                        ->whereBetween('payment_date', [$session->opened_at, $session->closed_at])
                        ->where('status', Payment::STATUS_COMPLETED)
                        ->count(),
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al cerrar sesión de caja:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar la caja',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}