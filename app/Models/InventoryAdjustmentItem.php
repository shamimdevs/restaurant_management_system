<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustmentItem extends Model
{
    protected $fillable = [
        'inventory_adjustment_id', 'ingredient_id',
        'system_quantity', 'actual_quantity', 'difference', 'notes',
    ];

    protected $casts = [
        'system_quantity' => 'float',
        'actual_quantity' => 'float',
        'difference'      => 'float',
    ];

    public function adjustment(): BelongsTo  { return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id'); }
    public function ingredient(): BelongsTo  { return $this->belongsTo(Ingredient::class); }
}
