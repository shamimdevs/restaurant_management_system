<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'branch_id', 'customer_id', 'restaurant_table_id', 'reservation_code',
        'guest_name', 'guest_phone', 'pax', 'reserved_at', 'duration_minutes',
        'status', 'special_requests', 'notes', 'confirmed_by',
    ];

    protected $casts = [
        'reserved_at'      => 'datetime',
        'duration_minutes' => 'integer',
        'pax'              => 'integer',
    ];

    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo     { return $this->belongsTo(Customer::class); }
    public function table(): BelongsTo        { return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id'); }
    public function confirmedBy(): BelongsTo  { return $this->belongsTo(User::class, 'confirmed_by'); }

    public function scopeUpcoming($query)
    {
        return $query->where('reserved_at', '>=', now())->whereIn('status', ['pending', 'confirmed']);
    }
}
