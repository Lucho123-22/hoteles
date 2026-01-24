<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PaymentMethod extends Model{
    use HasFactory, SoftDeletes, HasUuids;
    protected $fillable = [
        'name',
        'code',
        'requires_reference',
        'is_active',
        'sort_order'
    ];
    protected $casts = [
        'requires_reference' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];
    public function payments(){
        return $this->hasMany(Payment::class);
    }
    public function scopeActive($query){
        return $query->where('is_active', true);
    }
    public function scopeOrdered($query){
        return $query->orderBy('sort_order')->orderBy('name');
    }
    public function requiresReference(): bool{
        return $this->requires_reference;
    }
}