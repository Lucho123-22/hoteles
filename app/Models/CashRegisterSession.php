<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class CashRegisterSession extends Model implements Auditable{
    use HasFactory,
        HasUuids,
        SoftDeletes,
        HasAuditFields,
        \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'cash_register_id',
        'status',
        'opened_by',
        'closed_by',
        'opening_amount',
        'system_total_amount',
        'counted_total_amount',
        'difference_amount',
        'opened_at',
        'closed_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'opening_amount'       => 'decimal:2',
        'system_total_amount'  => 'decimal:2',
        'counted_total_amount' => 'decimal:2',
        'difference_amount'    => 'decimal:2',
        'opened_at'            => 'datetime',
        'closed_at'            => 'datetime',
    ];
    public function cashRegister(){
        return $this->belongsTo(CashRegister::class);
    }
    public function openedBy(){
        return $this->belongsTo(User::class, 'opened_by');
    }
    public function closedBy(){
        return $this->belongsTo(User::class, 'closed_by');
    }
    public function scopeOpen($query){
        return $query
            ->where('status', 'abierta')
            ->whereNull('closed_at');
    }
    public function scopeForUser($query, int $userId){
        return $query->where('opened_by', $userId);
    }
    public static function userHasOpenSession(int $userId): bool{
        return self::query()
            ->forUser($userId)
            ->open()
            ->exists();
    }
    public static function getUserOpenSession(int $userId): ?self{
        return self::query()
            ->forUser($userId)
            ->open()
            ->first();
    }
}
