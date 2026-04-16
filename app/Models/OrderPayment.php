<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id', 'branch_id', 'payment_method', 'amount',
        'reference_number', 'status', 'gateway_response', 'paid_at', 'received_by',
    ];

    protected $casts = [
        'amount'           => 'float',
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
    ];

    public function order(): BelongsTo        { return $this->belongsTo(Order::class); }
    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function receivedBy(): BelongsTo   { return $this->belongsTo(User::class, 'received_by'); }

    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
}
