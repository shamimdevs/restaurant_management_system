<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemVariant extends Model
{
    protected $fillable = [
        'menu_item_id', 'name', 'price', 'cost_price',
        'sku', 'is_default', 'is_available', 'sort_order',
    ];

    protected $casts = [
        'price'        => 'float',
        'cost_price'   => 'float',
        'is_default'   => 'boolean',
        'is_available' => 'boolean',
    ];

    public function menuItem(): BelongsTo { return $this->belongsTo(MenuItem::class); }
}
