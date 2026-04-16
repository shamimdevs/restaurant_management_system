<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenTicketItem extends Model
{
    protected $fillable = [
        'kitchen_ticket_id', 'order_item_id', 'item_name', 'variant_name',
        'modifiers', 'quantity', 'notes', 'status', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'modifiers'    => 'array',
        'quantity'     => 'integer',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Alias for frontend compatibility
    protected $appends = ['name'];

    public function getNameAttribute(): string
    {
        return $this->item_name ?? '';
    }

    public function ticket(): BelongsTo    { return $this->belongsTo(KitchenTicket::class, 'kitchen_ticket_id'); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
