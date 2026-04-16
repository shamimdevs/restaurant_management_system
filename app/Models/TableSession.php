<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableSession extends Model
{
    protected $fillable = [
        'branch_id', 'restaurant_table_id', 'waiter_id',
        'customer_id', 'pax', 'status', 'started_at', 'ended_at', 'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
    public function table(): BelongsTo   { return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id'); }
    public function waiter(): BelongsTo  { return $this->belongsTo(User::class, 'waiter_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function orders(): HasMany    { return $this->hasMany(Order::class); }

    public function scopeActive($query)  { return $query->where('status', 'active'); }

    public function close(): void
    {
        $this->update(['status' => 'closed', 'ended_at' => now()]);
        $this->table->markAvailable();
    }

    public function getDurationAttribute(): string
    {
        $end = $this->ended_at ?? now();
        return $this->started_at->diffForHumans($end, true);
    }
}
