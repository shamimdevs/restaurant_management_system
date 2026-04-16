<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPointTransaction extends Model
{
    protected $fillable = [
        'customer_id', 'company_id', 'order_id', 'type',
        'points', 'balance_after', 'description', 'expires_at',
    ];

    protected $casts = [
        'points'       => 'integer',
        'balance_after' => 'integer',
        'expires_at'   => 'datetime',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function order(): BelongsTo    { return $this->belongsTo(Order::class); }
}
