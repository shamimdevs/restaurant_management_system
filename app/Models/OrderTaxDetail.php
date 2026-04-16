<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTaxDetail extends Model
{
    protected $fillable = [
        'order_id', 'tax_rate_id', 'tax_name', 'tax_rate', 'taxable_amount', 'tax_amount',
    ];

    protected $casts = [
        'tax_rate'       => 'float',
        'taxable_amount' => 'float',
        'tax_amount'     => 'float',
    ];

    public function order(): BelongsTo   { return $this->belongsTo(Order::class); }
    public function taxRate(): BelongsTo { return $this->belongsTo(TaxRate::class); }
}
