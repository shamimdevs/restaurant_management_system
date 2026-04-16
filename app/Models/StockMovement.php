<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'branch_id', 'ingredient_id', 'unit_id', 'type', 'quantity',
        'unit_cost', 'total_cost', 'stock_before', 'stock_after',
        'reference_type', 'reference_id', 'user_id', 'notes', 'movement_date',
    ];

    protected $appends = ['movement_type', 'balance_after'];

    protected $casts = [
        'quantity'      => 'float',
        'unit_cost'     => 'float',
        'total_cost'    => 'float',
        'stock_before'  => 'float',
        'stock_after'   => 'float',
        'movement_date' => 'date',
    ];

    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
    public function unit(): BelongsTo       { return $this->belongsTo(Unit::class); }
    public function user(): BelongsTo       { return $this->belongsTo(User::class); }
    public function reference(): MorphTo    { return $this->morphTo(); }

    public function scopeForBranch($query, ?int $branchId) { return $query->where('branch_id', $branchId); }

    /** Frontend expects movement_type ('in' | 'out' | 'adjustment') */
    public function getMovementTypeAttribute(): string
    {
        return match ($this->type) {
            'purchase', 'receipt', 'return_in', 'opening_stock', 'adjustment_in' => 'in',
            'usage', 'waste', 'spoilage', 'transfer_out', 'adjustment_out'        => 'out',
            default                                                                => 'adjustment',
        };
    }

    /** Frontend expects balance_after instead of stock_after */
    public function getBalanceAfterAttribute(): float
    {
        return (float) $this->stock_after;
    }
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('movement_date', [$from, $to]);
    }
}
