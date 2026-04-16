<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenTicket extends Model
{
    protected $fillable = [
        'order_id', 'branch_id', 'ticket_number', 'table_label', 'order_type',
        'status', 'priority', 'notes', 'station',
        'cooking_started_at', 'ready_at', 'served_at', 'bump_count',
    ];

    protected $casts = [
        'cooking_started_at' => 'datetime',
        'ready_at'           => 'datetime',
        'served_at'          => 'datetime',
        'priority'           => 'integer',
        'bump_count'         => 'integer',
    ];

    public function order(): BelongsTo    { return $this->belongsTo(Order::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function items(): HasMany      { return $this->hasMany(KitchenTicketItem::class); }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopePending($query)  { return $query->where('status', 'pending'); }
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'cooking']);
    }

    // ── Status transitions ───────────────────────────────────────────────

    public function startCooking(): void
    {
        $this->update(['status' => 'cooking', 'cooking_started_at' => now()]);
    }

    public function markReady(): void
    {
        $this->update(['status' => 'ready', 'ready_at' => now()]);
        $this->order->update(['status' => 'ready']);
    }

    public function markServed(): void
    {
        $this->update(['status' => 'served', 'served_at' => now()]);
    }

    public function getWaitTimeAttribute(): ?int
    {
        if (! $this->cooking_started_at) return null;
        $end = $this->ready_at ?? now();
        return (int) $this->cooking_started_at->diffInMinutes($end);
    }
}
