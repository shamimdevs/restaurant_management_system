<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedback extends Model
{
    protected $fillable = [
        'customer_id', 'branch_id', 'order_id', 'overall_rating',
        'food_rating', 'service_rating', 'ambiance_rating',
        'comment', 'is_public', 'responded_at', 'response',
    ];

    protected $casts = [
        'overall_rating'  => 'integer',
        'food_rating'     => 'integer',
        'service_rating'  => 'integer',
        'ambiance_rating' => 'integer',
        'is_public'       => 'boolean',
        'responded_at'    => 'datetime',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function order(): BelongsTo    { return $this->belongsTo(Order::class); }
}
