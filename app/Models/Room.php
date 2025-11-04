<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Events\RoomStatusChanged;
use App\Jobs\UpdateRoomStatusJob;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Room extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'floor_id', 'room_type_id', 'room_number', 'name', 'status_changed_at',
        'description', 'status', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const STATUS_AVAILABLE = 'available';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_CLEANING = 'cleaning';

    // Relaciones
    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(RoomStatusLog::class)->latest();
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)->where('is_active', true);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', self::STATUS_OCCUPIED);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->whereHas('floor', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });
    }
    public function currentBooking()
    {
        return $this->hasOne(Booking::class)
            ->where('status', Booking::STATUS_CHECKED_IN)
            ->with('customer') // Eager load del cliente
            ->latest('check_in');
    }
    // Métodos de negocio
    public function changeStatus($newStatus, $reason = null, $userId = null)
    {
        $oldStatus = $this->status;
        
        if ($oldStatus !== $newStatus) {
            $this->status = $newStatus;
            $this->save();

            // Dispatch job para tareas pesadas
            UpdateRoomStatusJob::dispatch($this, $oldStatus, $newStatus, $reason, $userId);
            
            // Evento para tiempo real
            event(new RoomStatusChanged($this, $oldStatus, $newStatus));
        }
    }

    public function isAvailable()
    {
        return $this->status === self::STATUS_AVAILABLE && $this->is_active;
    }

    public function canBeBooked()
    {
        return $this->isAvailable() && !$this->hasActiveBooking();
    }

    public function hasActiveBooking()
    {
        return $this->bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->exists();
    }

public function getFullNameAttribute()
{
    $branchName = $this->floor?->subBranch?->branch?->name ?? 'Sin sucursal';
    $floorName = $this->floor?->name ?? 'Sin piso';
    $roomNumber = $this->room_number ?? 'Sin número';

    return "{$branchName} - {$floorName} - {$roomNumber}";
}

    protected static function boot()
    {
        parent::boot();
        
        static::updating(function ($room) {
            if ($room->isDirty('status')) {
                $room->status_changed_at = now();
            }
        });
    }
    public function activeBooking(){
        return $this->hasOne(Booking::class)
            ->where('status', 'checked_in')
            ->orderByDesc('check_in'); // 👈 reemplaza latestOfMany()
    }
}