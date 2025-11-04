<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubBranchProduct extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'sub_branch_id',
        'product_id',
        'packages_in_stock',
        'units_per_package',
        'current_stock',
        'min_stock',
        'max_stock',
        'is_fractionable',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'packages_in_stock' => 'integer',
        'units_per_package' => 'integer',
        'current_stock' => 'integer',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
        'is_fractionable' => 'boolean',
        'is_active' => 'boolean',
    ];
    // Relaciones
    public function subBranch(){
        return $this->belongsTo(SubBranch::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function createdBy(){
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(){
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(){
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getSubBranchStock($subBranchId)
    {
        return $this->subBranchProducts()
            ->where('sub_branch_id', $subBranchId)
            ->first();
    }

    // Scopes
    public function scopeActive($query){
        return $query->where('is_active', true);
    }
    public function scopeLowStock($query){
        return $query->whereRaw('current_stock <= min_stock');
    }
    public function scopeBySubBranch($query, $subBranchId){
        return $query->where('sub_branch_id', $subBranchId);
    }
    public function calculateCurrentStock(): int{
        return ($this->packages_in_stock * $this->units_per_package);
    }
    public function updateCurrentStock(): void{
        $stockAnterior = $this->current_stock;
        $this->current_stock = $this->calculateCurrentStock();
        $this->save();
        $this->registerKardex($stockAnterior);
    }
    public function isLowStock(): bool{
        return $this->current_stock <= $this->min_stock;
    }
}
