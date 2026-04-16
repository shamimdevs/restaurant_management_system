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

    public function scopeForBranch($query, int $branchId) { return $query->where('branch_id', $branchId); }
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('movement_date', [$from, $to]);
    }
}
