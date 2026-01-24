<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'dni',
        'apellidos',
        'nacimiento',
        'email',
        'username',
        'password',
        'status',
        'restablecimiento',
        'sub_branch_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'username', 'status'])
            ->useLogName('usuario')
            ->logOnlyDirty();
    }

    public function isOnline(): bool
    {
        return cache()->has('user-is-online-' . $this->id);
    }

    // ─── Relaciones ───────────────────────────────
    public function subBranch()
    {
        return $this->belongsTo(SubBranch::class);
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'usuario_id');
    }
    // Relación con pagos
    public function pagos()
    {
        return $this->hasMany(PagoPersonal::class, 'user_id');
    }

    // Relación con sucursal
    public function sucursal()
    {
        return $this->belongsTo(SubBranch::class, 'sub_branch_id');
    }

    // Método auxiliar para obtener total pagado
    public function totalPagado($periodo = null)
    {
        $query = $this->pagos()->where('estado', 'pagado');
        
        if ($periodo) {
            $query->where('periodo', $periodo);
        }
        
        return $query->sum('monto');
    }

    // Scope para filtrar por sucursal
    public function scopePorSucursal($query, $subBranchId)
    {
        return $query->where('sub_branch_id', $subBranchId);
    }
    public function activeCashRegister(){
        return $this->hasOneThrough(
            CashRegister::class,
            SubBranch::class,
            'id',
            'sub_branch_id',
            'sub_branch_id',
            'id'
        )->where('is_active', true)
        ->whereHas('currentSession', function($q) {
            $q->where('status', 'open');
        });
    }
    public function getActiveCashRegister(){
        if (!$this->sub_branch_id) {
            return null;
        }
        
        return CashRegister::where('sub_branch_id', $this->sub_branch_id)
            ->where('is_active', true)
            ->whereHas('currentSession', function($q) {
                $q->where('status', 'abierta')
                ->where('opened_by', Auth::id());
            })
            ->first();
    }
}
