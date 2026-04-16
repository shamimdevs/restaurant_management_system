<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'unit_id', 'name', 'description',
        'sku', 'barcode', 'cost_per_unit', 'current_stock',
        'min_stock_level', 'max_stock_level', 'reorder_point',
        'storage_location', 'is_active', 'track_stock',
    ];

    protected $casts = [
        'cost_per_unit'   => 'float',
        'current_stock'   => 'float',
        'min_stock_level' => 'float',
        'max_stock_level' => 'float',
        'reorder_point'   => 'float',
        'is_active'       => 'boolean',
        'track_stock'     => 'boolean',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function unit(): BelongsTo     { return $this->belongsTo(Unit::class); }
    public function movements(): HasMany  { return $this->hasMany(StockMovement::class); }
    public function alerts(): HasMany     { return $this->hasMany(StockAlert::class); }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)    { return $query->where('is_active', true); }
    public function scopeLowStock($query)  { return $query->whereColumn('current_stock', '<=', 'min_stock_level'); }
    public function scopeOutOfStock($query){ return $query->where('current_stock', '<=', 0); }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function isLowStock(): bool  { return $this->current_stock <= $this->min_stock_level; }
    public function isOutOfStock(): bool { return $this->current_stock <= 0; }

    public function getStockValueAttribute(): float
    {
        return round($this->current_stock * $this->cost_per_unit, 2);
    }

    /** Deduct stock and log the movement — called by InventoryService */
    public function deductStock(float $quantity, string $reference_type, int $reference_id, int $userId): void
    {
        $before = $this->current_stock;
        $this->decrement('current_stock', $quantity);

        $this->movements()->create([
            'branch_id'      => $this->branch_id,
            'unit_id'        => $this->unit_id,
            'type'           => 'usage',
            'quantity'       => -$quantity,
            'unit_cost'      => $this->cost_per_unit,
            'total_cost'     => round($quantity * $this->cost_per_unit, 2),
            'stock_before'   => $before,
            'stock_after'    => $this->current_stock,
            'reference_type' => $reference_type,
            'reference_id'   => $reference_id,
            'user_id'        => $userId,
            'movement_date'  => today(),
        ]);

        // Trigger low-stock alert if needed
        if ($this->isLowStock() && ! $this->alerts()->where('is_resolved', false)->exists()) {
            $this->alerts()->create([
                'branch_id'          => $this->branch_id,
                'alert_type'         => $this->isOutOfStock() ? 'out_of_stock' : 'low_stock',
                'current_quantity'   => $this->current_stock,
                'threshold_quantity' => $this->min_stock_level,
            ]);
        }
    }
}
