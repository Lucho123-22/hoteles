<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingConsumption extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'booking_id', 'product_id', 'quantity', 'unit_price', 'total_price','status',
        'consumed_at', 'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'consumed_at' => 'datetime',
    ];

    // Relaciones
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('consumed_at', today());
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->whereHas('booking', function ($q) use ($roomId) {
            $q->where('room_id', $roomId);
        });
    }
}
