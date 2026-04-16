<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = [
        'menu_item_id', 'variant_id', 'yield_quantity', 'yield_unit_id',
        'instructions', 'prep_time_minutes', 'cook_time_minutes', 'is_active',
    ];

    protected $casts = [
        'yield_quantity'    => 'float',
        'prep_time_minutes' => 'integer',
        'cook_time_minutes' => 'integer',
        'is_active'         => 'boolean',
    ];

    public function menuItem(): BelongsTo  { return $this->belongsTo(MenuItem::class); }
    public function variant(): BelongsTo   { return $this->belongsTo(MenuItemVariant::class, 'variant_id'); }
    public function yieldUnit(): BelongsTo { return $this->belongsTo(Unit::class, 'yield_unit_id'); }
    public function ingredients(): HasMany { return $this->hasMany(RecipeIngredient::class); }

    /** Calculate ingredient cost for one serving */
    public function getCostPerServingAttribute(): float
    {
        return $this->ingredients->sum(fn ($ri) =>
            ($ri->quantity / ($this->yield_quantity ?: 1)) * $ri->ingredient->cost_per_unit
        );
    }
}
