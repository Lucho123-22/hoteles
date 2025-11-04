<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\GeneratesCode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Product extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, GeneratesCode, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'purchase_price',
        'sale_price',
        'unit_type',
        'is_active',
        'is_fractionable',
        'fraction_units',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_fractionable' => 'boolean',
        'fraction_units' => 'integer',
    ];

    // Relaciones
    public function category()
    {
        return $this->belongsTo(Categoria::class, 'category_id');
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function consumptions()
    {
        return $this->hasMany(BookingConsumption::class);
    }

    public function subBranchProducts()
    {
        return $this->hasMany(SubBranchProduct::class, 'product_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query, $branchId)
    {
        return $query->whereHas('inventory', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->where('current_stock', '>', 0);
        });
    }

    // Métodos auxiliares
    public function getStockForBranch($branchId)
    {
        return $this->inventory()->where('branch_id', $branchId)->first()?->current_stock ?? 0;
    }

    public function isInStockForBranch($branchId, $quantity = 1)
    {
        return $this->getStockForBranch($branchId) >= $quantity;
    }

    public function isFractionable(): bool
    {
        return $this->is_fractionable;
    }

    public function getFractionUnits(): int
    {
        return $this->fraction_units ?: 1;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $product->code = $product->generateUniqueCode('PR');
            }
        });
    }
}
