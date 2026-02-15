<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class RateType extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Constantes
    public const CODE_HOURLY = 'HOURLY';  // ACTUALIZADO
    public const CODE_DAILY = 'DAILY';    // ACTUALIZADO
    public const CODE_NIGHTLY = 'NIGHTLY'; // ACTUALIZADO

    // Mantener compatibilidad con código antiguo
    public const CODE_HOUR = 'HOURLY';
    public const CODE_DAY = 'DAILY';
    public const CODE_NIGHT = 'NIGHTLY';

    // ==========================================
    // RELACIONES
    // ==========================================
    
    public function pricingRanges()  // NUEVA RELACIÓN
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

    public function scopeByCode($query, $code)
    {
        return $query->where('code', strtoupper($code));
    }

    public function scopeHourly($query)
    {
        return $query->where('code', self::CODE_HOURLY);
    }

    public function scopeDaily($query)
    {
        return $query->where('code', self::CODE_DAILY);
    }

    public function scopeNightly($query)
    {
        return $query->where('code', self::CODE_NIGHTLY);
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
    
    /**
     * Verificar si es tarifa por horas
     */
    public function isHourly(): bool
    {
        return $this->code === self::CODE_HOURLY;
    }

    /**
     * Verificar si es tarifa por día
     */
    public function isDaily(): bool
    {
        return $this->code === self::CODE_DAILY;
    }

    /**
     * Verificar si es tarifa por noche
     */
    public function isNightly(): bool
    {
        return $this->code === self::CODE_NIGHTLY;
    }

    /**
     * Verificar si requiere rango de tiempo
     */
    public function requiresTimeRange(): bool
    {
        return $this->isHourly();
    }

    /**
     * Obtener todos los precios activos de este tipo de tarifa
     */
    public function getActivePrices(string $subBranchId, ?string $roomTypeId = null)
    {
        $query = $this->pricingRanges()
                      ->where('sub_branch_id', $subBranchId)
                      ->active()
                      ->effectiveNow()
                      ->with('roomType');

        if ($roomTypeId) {
            $query->where('room_type_id', $roomTypeId);
        }

        return $query->orderBy('price')->get();
    }

    /**
     * Obtener estadísticas de precios
     */
    public function getPriceStats(string $subBranchId): array
    {
        $prices = $this->pricingRanges()
                       ->where('sub_branch_id', $subBranchId)
                       ->active()
                       ->effectiveNow()
                       ->pluck('price');

        if ($prices->isEmpty()) {
            return [
                'min' => 0,
                'max' => 0,
                'avg' => 0,
                'count' => 0,
            ];
        }

        return [
            'min' => $prices->min(),
            'max' => $prices->max(),
            'avg' => round($prices->avg(), 2),
            'count' => $prices->count(),
        ];
    }

    /**
     * Obtener nombre legible
     */
    public function getDisplayName(): string
    {
        return match($this->code) {
            self::CODE_HOURLY => 'Por Horas',
            self::CODE_DAILY => 'Por Día',
            self::CODE_NIGHTLY => 'Por Noche',
            default => $this->name,
        };
    }

    /**
     * Obtener icono sugerido (útil para UI)
     */
    public function getIcon(): string
    {
        return match($this->code) {
            self::CODE_HOURLY => 'clock',
            self::CODE_DAILY => 'sun',
            self::CODE_NIGHTLY => 'moon',
            default => 'calendar',
        };
    }
}
