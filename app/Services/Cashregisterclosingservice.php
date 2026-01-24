<?php

namespace App\Services;

use App\Models\CashRegisterSession;
use App\Models\CashRegisterSessionPaymentMethod;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CashRegisterClosingService{
    public function closeSession(
        CashRegisterSession $session,
        array $countedAmounts, // ['payment_method_id' => amount]
        ?int $userId = null
    ): CashRegisterSession {
        
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($session, $countedAmounts, $userId) {
            
            // 1. Calcular automáticamente los montos del sistema por método de pago
            $systemAmountsByMethod = $this->calculateSystemAmountsByPaymentMethod($session);
            
            // 2. Calcular el total del sistema
            $systemTotalAmount = collect($systemAmountsByMethod)->sum();
            
            // 3. Calcular el total contado
            $countedTotalAmount = collect($countedAmounts)->sum();
            
            // 4. Calcular la diferencia total
            $differenceAmount = $countedTotalAmount - $systemTotalAmount;
            
            // 5. Guardar los detalles por método de pago
            foreach ($systemAmountsByMethod as $paymentMethodId => $systemAmount) {
                $countedAmount = $countedAmounts[$paymentMethodId] ?? 0;
                $difference = $countedAmount - $systemAmount;
                
                CashRegisterSessionPaymentMethod::create([
                    'cash_register_session_id' => $session->id,
                    'payment_method_id' => $paymentMethodId,
                    'system_amount' => $systemAmount,
                    'counted_amount' => $countedAmount,
                    'difference_amount' => $difference,
                ]);
            }
            
            // 6. Actualizar la sesión
            $session->update([
                'status' => 'cerrada',
                'closed_by' => $userId,
                'closed_at' => now(),
                'system_total_amount' => $systemTotalAmount,
                'counted_total_amount' => $countedTotalAmount,
                'difference_amount' => $differenceAmount,
                'updated_by' => $userId,
            ]);
            
            // 7. Liberar la caja (quitar current_session_id)
            $session->cashRegister->update([
                'current_session_id' => null,
                'updated_by' => $userId,
            ]);
            
            return $session->fresh();
        });
    }
    protected function calculateSystemAmountsByPaymentMethod(CashRegisterSession $session): array{
        $payments = Payment::where('cash_register_session_id', $session->id)
            ->where('status', 'completed')
            ->get();
        $amountsByMethod = [];
        foreach ($payments as $payment) {
            $methodId = $payment->payment_method_id;
            if (!isset($amountsByMethod[$methodId])) {
                $amountsByMethod[$methodId] = 0;
            }
            $amountsByMethod[$methodId] += $payment->amount;
        }
        return $amountsByMethod;
    }
    public function getSessionSummary(CashRegisterSession $session): array{
        $systemAmountsByMethod = $this->calculateSystemAmountsByPaymentMethod($session);
        $systemTotalAmount = collect($systemAmountsByMethod)->sum();
        $paymentMethods = PaymentMethod::whereIn('id', array_keys($systemAmountsByMethod))
            ->get()
            ->keyBy('id');
        $methodDetails = [];
        foreach ($systemAmountsByMethod as $methodId => $amount) {
            $methodDetails[] = [
                'payment_method_id' => $methodId,
                'payment_method_name' => $paymentMethods[$methodId]->name ?? 'Desconocido',
                'system_amount' => $amount,
            ];
        }
        $totalTransactions = Payment::where('cash_register_session_id', $session->id)
            ->where('status', 'completed')
            ->count();
        return [
            'opening_amount' => $session->opening_amount,
            'system_total_amount' => $systemTotalAmount,
            'expected_total' => $session->opening_amount + $systemTotalAmount,
            'total_transactions' => $totalTransactions,
            'payment_methods' => $methodDetails,
            'opened_at' => $session->opened_at,
            'opened_by' => $session->openedBy->name ?? 'Desconocido',
        ];
    }
    public function canCloseSession(CashRegisterSession $session): array{
        $errors = [];
        if ($session->status !== 'abierta') {
            $errors[] = 'La sesión ya está cerrada';
        }
        if ($session->closed_at !== null) {
            $errors[] = 'La sesión ya tiene fecha de cierre';
        }
        $pendingPayments = Payment::where('cash_register_session_id', $session->id)
            ->where('status', 'pending')
            ->count();
        if ($pendingPayments > 0) {
            $errors[] = "Hay {$pendingPayments} pago(s) pendiente(s) de procesar";
        }
        return [
            'can_close' => empty($errors),
            'errors' => $errors,
        ];
    }
}