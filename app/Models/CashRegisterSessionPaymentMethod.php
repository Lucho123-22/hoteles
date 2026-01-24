<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegisterSessionPaymentMethod extends Model{
    use HasFactory, HasUuids;
    protected $fillable = [
        'cash_register_session_id',
        'payment_method_id',
        'system_amount',
        'counted_amount',
        'difference_amount',
    ];
    protected $casts = [
        'system_amount' => 'decimal:2',
        'counted_amount' => 'decimal:2',
        'difference_amount' => 'decimal:2',
    ];
    public function session(){
        return $this->belongsTo(
            CashRegisterSession::class,
            'cash_register_session_id'
        );
    }
    public function paymentMethod(){
        return $this->belongsTo(PaymentMethod::class);
    }
}
