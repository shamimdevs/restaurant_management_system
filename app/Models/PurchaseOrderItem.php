<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'ingredient_id', 'unit_id',
        'quantity', 'unit_price', 'vat_rate', 'vat_amount',
        'total_price', 'received_quantity', 'notes',
    ];

    protected $casts = [
        'quantity'          => 'float',
        'unit_price'        => 'float',
        'vat_rate'          => 'float',
        'vat_amount'        => 'float',
        'total_price'       => 'float',
        'received_quantity' => 'float',
    ];

    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function ingredient(): BelongsTo    { return $this->belongsTo(Ingredient::class); }
    public function unit(): BelongsTo          { return $this->belongsTo(Unit::class); }

    public function getPendingQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->received_quantity);
    }
}
