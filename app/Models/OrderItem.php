<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'menu_item_id', 'variant_id', 'item_name', 'variant_name',
        'unit_price', 'cost_price', 'quantity', 'discount_amount',
        'tax_amount', 'subtotal', 'total', 'status', 'notes', 'is_complimentary',
    ];

    protected $casts = [
        'unit_price'      => 'float',
        'cost_price'      => 'float',
        'quantity'        => 'integer',
        'discount_amount' => 'float',
        'tax_amount'      => 'float',
        'subtotal'        => 'float',
        'total'           => 'float',
        'is_complimentary' => 'boolean',
    ];

    public function order(): BelongsTo     { return $this->belongsTo(Order::class); }
    public function menuItem(): BelongsTo  { return $this->belongsTo(MenuItem::class); }
    public function variant(): BelongsTo   { return $this->belongsTo(MenuItemVariant::class, 'variant_id'); }
    public function modifiers(): HasMany   { return $this->hasMany(OrderItemModifier::class); }

    public function getModifiersTotalAttribute(): float
    {
        return $this->modifiers->sum(fn ($m) => $m->price * $m->quantity);
    }
}
