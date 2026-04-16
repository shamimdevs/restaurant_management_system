<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modifier extends Model
{
    protected $fillable = [
        'modifier_group_id', 'name', 'price', 'cost_price',
        'is_default', 'is_available', 'sort_order',
    ];

    protected $casts = [
        'price'        => 'float',
        'cost_price'   => 'float',
        'is_default'   => 'boolean',
        'is_available' => 'boolean',
    ];

    public function group(): BelongsTo { return $this->belongsTo(ModifierGroup::class, 'modifier_group_id'); }
}
