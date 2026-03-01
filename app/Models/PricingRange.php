<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PricingRange extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'sub_branch_id',
        'room_type_id',
        'rate_type_id',
        'time_from_minutes',
        'time_to_minutes',
        'price',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'time_from_minutes' => 'integer',
        'time_to_minutes' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    // Relaciones
    public function subBranch()
    {
        return $this->belongsTo(SubBranch::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function rateType()
    {
        return $this->belongsTo(RateType::class);
    }

    public function bookings(){
        return $this->hasMany(Booking::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySubBranch($query, $subBranchId)
    {
        return $query->where('sub_branch_id', $subBranchId);
    }

    public function scopeByRoomType($query, $roomTypeId)
    {
        return $query->where('room_type_id', $roomTypeId);
    }

    public function scopeByRateType($query, $rateTypeId)
    {
        return $query->where('rate_type_id', $rateTypeId);
    }

    public function scopeHourly($query)
    {
        return $query->whereHas('rateType', function ($q) {
            $q->where('code', 'HOURLY');
        });
    }

    public function scopeForMinutes($query, $minutes)
    {
        return $query->where('time_from_minutes', '<=', $minutes)
                     ->where('time_to_minutes', '>=', $minutes);
    }

    public function scopeEffectiveNow($query)
    {
        return $query->where('effective_from', '<=', now())
                     ->where(function ($q) {
                         $q->whereNull('effective_to')
                           ->orWhere('effective_to', '>=', now());
                     });
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
                     ->where(function ($q) use ($date) {
                         $q->whereNull('effective_to')
                           ->orWhere('effective_to', '>=', $date);
                     });
    }

    public function scopeOrderByTime($query)
    {
        // PostgreSQL: NULLS LAST para manejar valores null correctamente
        return $query->orderByRaw('time_from_minutes NULLS LAST');
    }

    public function scopeOrderByPrice($query, $direction = 'asc')
    {
        return $query->orderBy('price', $direction);
    }

    // Métodos auxiliares
    public function isInRange($minutes)
    {
        if (is_null($this->time_from_minutes) || is_null($this->time_to_minutes)) {
            return false;
        }
        
        return $minutes >= $this->time_from_minutes && 
               $minutes <= $this->time_to_minutes;
    }

    public function isEffective($date = null): bool
    {
        $date = ($date ?? now())->toDateString();

        return $date >= $this->effective_from->toDateString()
            && (
                is_null($this->effective_to) ||
                $date <= $this->effective_to->toDateString()
            );
    }


    public function getDurationInHours()
    {
        if (is_null($this->time_from_minutes) || is_null($this->time_to_minutes)) {
            return null;
        }
        
        return ($this->time_to_minutes - $this->time_from_minutes) / 60;
    }

    public function getFormattedTimeRange()
    {
        if (is_null($this->time_from_minutes) || is_null($this->time_to_minutes)) {
            return 'N/A';
        }
        
        $hoursFrom = floor($this->time_from_minutes / 60);
        $minutesFrom = $this->time_from_minutes % 60;
        $hoursTo = floor($this->time_to_minutes / 60);
        $minutesTo = $this->time_to_minutes % 60;

        $from = $hoursFrom . 'h';
        if ($minutesFrom > 0) {
            $from .= ' ' . $minutesFrom . 'min';
        }

        $to = $hoursTo . 'h';
        if ($minutesTo > 0) {
            $to .= ' ' . $minutesTo . 'min';
        }

        return $from . ' - ' . $to;
    }

    public function getPricePerHour()
    {
        $durationHours = $this->getDurationInHours();
        return $durationHours > 0 ? $this->price / $durationHours : 0;
    }

    public function isHourlyRate()
    {
        return $this->rateType && $this->rateType->code === 'HOURLY';
    }

    public function isDailyRate()
    {
        return $this->rateType && $this->rateType->code === 'DAILY';
    }

    public function isNightlyRate()
    {
        return $this->rateType && $this->rateType->code === 'NIGHTLY';
    }

    // Métodos estáticos
    public static function findPrice(
        string $subBranchId,
        string $roomTypeId,
        string $rateTypeId,
        ?int $minutes = null,
        $date = null
    ): ?self {
        $query = self::where('sub_branch_id', $subBranchId)
                     ->where('room_type_id', $roomTypeId)
                     ->where('rate_type_id', $rateTypeId)
                     ->active()
                     ->effectiveOn($date ?? now());

        if (!is_null($minutes)) {
            $query->forMinutes($minutes);
        }

        return $query->first();
    }

    public static function getAvailableRanges(
        string $subBranchId,
        string $roomTypeId,
        ?string $rateTypeCode = null,
        $date = null
    ) {
        $query = self::with(['rateType', 'roomType'])
                     ->where('sub_branch_id', $subBranchId)
                     ->where('room_type_id', $roomTypeId)
                     ->active()
                     ->effectiveOn($date ?? now());

        if ($rateTypeCode) {
            $query->whereHas('rateType', function ($q) use ($rateTypeCode) {
                $q->where('code', $rateTypeCode);
            });
        }

        return $query->orderByRaw('time_from_minutes NULLS LAST')->get();
    }

    public static function hasOverlap(
        string $subBranchId,
        string $roomTypeId,
        string $rateTypeId,
        ?int $timeFrom,
        ?int $timeTo,
        $effectiveFrom,
        $effectiveTo = null,
        ?string $excludeId = null
    ): bool {
        $query = self::where('sub_branch_id', $subBranchId)
                     ->where('room_type_id', $roomTypeId)
                     ->where('rate_type_id', $rateTypeId)
                     ->active();

        // Verificar solapamiento de tiempo (solo para tarifas por horas)
        if (!is_null($timeFrom) && !is_null($timeTo)) {
            $query->where(function ($q) use ($timeFrom, $timeTo) {
                $q->whereBetween('time_from_minutes', [$timeFrom, $timeTo])
                  ->orWhereBetween('time_to_minutes', [$timeFrom, $timeTo])
                  ->orWhere(function ($q2) use ($timeFrom, $timeTo) {
                      $q2->where('time_from_minutes', '<=', $timeFrom)
                         ->where('time_to_minutes', '>=', $timeTo);
                  });
            });
        }

        // Verificar solapamiento de fechas de vigencia
        $query->where(function ($q) use ($effectiveFrom, $effectiveTo) {
            $q->where(function ($q2) use ($effectiveFrom) {
                $q2->where('effective_from', '<=', $effectiveFrom)
                   ->where(function ($q3) use ($effectiveFrom) {
                       $q3->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $effectiveFrom);
                   });
            })->orWhere(function ($q2) use ($effectiveFrom, $effectiveTo) {
                if ($effectiveTo) {
                    $q2->whereBetween('effective_from', [$effectiveFrom, $effectiveTo]);
                } else {
                    $q2->where('effective_from', '>=', $effectiveFrom);
                }
            });
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public static function getCheapestPrice(
        string $subBranchId,
        string $roomTypeId,
        ?string $rateTypeCode = null
    ): ?self {
        $query = self::where('sub_branch_id', $subBranchId)
                     ->where('room_type_id', $roomTypeId)
                     ->active()
                     ->effectiveNow();

        if ($rateTypeCode) {
            $query->whereHas('rateType', function ($q) use ($rateTypeCode) {
                $q->where('code', $rateTypeCode);
            });
        }

        return $query->orderBy('price', 'asc')->first();
    }

    public static function getPricesByRateType(
        string $subBranchId,
        string $roomTypeId
    ): array {
        return self::with('rateType')
                   ->where('sub_branch_id', $subBranchId)
                   ->where('room_type_id', $roomTypeId)
                   ->active()
                   ->effectiveNow()
                   ->get()
                   ->groupBy('rateType.code')
                   ->toArray();
    }
}