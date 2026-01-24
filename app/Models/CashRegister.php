<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OwenIt\Auditing\Contracts\Auditable;

class CashRegister extends Model implements Auditable{
    use HasFactory, HasUuids, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'sub_branch_id',
        'name',
        'current_session_id',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function subBranch(){
        return $this->belongsTo(SubBranch::class, 'sub_branch_id');
    }
    public function sessions(){
        return $this->hasMany(CashRegisterSession::class);
    }
    public function currentSession(){
        return $this->belongsTo(
            CashRegisterSession::class,
            'current_session_id'
        );
    }
    public function createdByUser(){
        return $this->belongsTo(User::class, 'created_by');
    }
    public function currentSessionWithOpenedBy(){
        return $this->belongsTo(
            CashRegisterSession::class,
            'current_session_id'
        )->with('openedByUser');
    }
    public function isOpen(): bool{
        return $this->is_active
            && $this->currentSession
            && $this->currentSession->status === 'abierta';
    }
}