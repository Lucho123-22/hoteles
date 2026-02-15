<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;

class RoomType extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'code',
        'description',
        'capacity',
        'max_capacity',
        'category',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'max_capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    // Constantes
    public const CATEGORY_ECONOMICA = 'Económica';
    public const CATEGORY_ESTANDAR = 'Estándar';
    public const CATEGORY_PREMIUM = 'Premium';
    public const CATEGORY_LUJO = 'Lujo';

    public static function getCategories()
    {
        return [
            self::CATEGORY_ECONOMICA,
            self::CATEGORY_ESTANDAR,
            self::CATEGORY_PREMIUM,
            self::CATEGORY_LUJO,
        ];
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($roomType) {
            if (empty($roomType->code)) {
                $roomType->code = self::generateCode();
            }
        });
    }

    // ==========================================
    // RELACIONES
    // ==========================================
    
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function pricingRanges()
    {
        return $this->hasMany(PricingRange::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // ==========================================
    // MUTATORS
    // ==========================================
    
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }

    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    public function hasAvailableRooms()
    {
        return $this->rooms()->where('status', 'available')->exists();
    }

    public function getAvailableRoomsCount()
    {
        return $this->rooms()->where('status', 'available')->count();
    }

    public function getActivePrices(string $subBranchId, ?string $rateTypeCode = null)
    {
        $query = $this->pricingRanges()
                      ->where('sub_branch_id', $subBranchId)
                      ->active()
                      ->effectiveNow()
                      ->with('rateType');

        if ($rateTypeCode) {
            $query->whereHas('rateType', function ($q) use ($rateTypeCode) {
                $q->where('code', $rateTypeCode);
            });
        }

        return $query->orderBy('time_from_minutes')->get();
    }

    public function getCheapestPrice(string $subBranchId, ?string $rateTypeCode = null)
    {
        $query = $this->pricingRanges()
                      ->where('sub_branch_id', $subBranchId)
                      ->active()
                      ->effectiveNow();

        if ($rateTypeCode) {
            $query->whereHas('rateType', function ($q) use ($rateTypeCode) {
                $q->where('code', $rateTypeCode);
            });
        }

        return $query->orderBy('price', 'asc')->first();
    }

    public function hasPrices(string $subBranchId): bool
    {
        return $this->pricingRanges()
                    ->where('sub_branch_id', $subBranchId)
                    ->active()
                    ->effectiveNow()
                    ->exists();
    }

    public function getPriceRange(string $subBranchId, ?string $rateTypeCode = null): array
    {
        $query = $this->pricingRanges()
                      ->where('sub_branch_id', $subBranchId)
                      ->active()
                      ->effectiveNow();

        if ($rateTypeCode) {
            $query->whereHas('rateType', function ($q) use ($rateTypeCode) {
                $q->where('code', $rateTypeCode);
            });
        }

        return [
            'min' => $query->min('price') ?? 0,
            'max' => $query->max('price') ?? 0,
        ];
    }

    // ==========================================
    // GENERACIÓN DE CÓDIGO (CORREGIDO PARA POSTGRESQL)
    // ==========================================
    
    private static function generateCode()
    {
        $prefix = 'RT';
        
        // PostgreSQL: usar SUBSTRING y CAST correctamente
        $lastRoomType = self::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING(code FROM 3) AS INTEGER) DESC")
            ->first();

        if ($lastRoomType) {
            $lastNumber = intval(substr($lastRoomType->code, 2));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}