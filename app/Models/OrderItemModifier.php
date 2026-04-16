<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModifier extends Model
{
    protected $fillable = [
        'order_item_id', 'modifier_group_id', 'modifier_id',
        'group_name', 'modifier_name', 'price', 'quantity',
    ];

    protected $casts = ['price' => 'float', 'quantity' => 'integer'];

    public function orderItem(): BelongsTo     { return $this->belongsTo(OrderItem::class); }
    public function modifierGroup(): BelongsTo { return $this->belongsTo(ModifierGroup::class); }
    public function modifier(): BelongsTo      { return $this->belongsTo(Modifier::class); }
}
