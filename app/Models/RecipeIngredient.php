<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    protected $fillable = ['recipe_id', 'ingredient_id', 'unit_id', 'quantity', 'waste_percentage', 'notes'];

    protected $casts = ['quantity' => 'float', 'waste_percentage' => 'float'];

    public function recipe(): BelongsTo     { return $this->belongsTo(Recipe::class); }
    public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
    public function unit(): BelongsTo       { return $this->belongsTo(Unit::class); }

    /** Effective quantity including waste */
    public function getEffectiveQuantityAttribute(): float
    {
        return round($this->quantity * (1 + $this->waste_percentage / 100), 4);
    }
}
