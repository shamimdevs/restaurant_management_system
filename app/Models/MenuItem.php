<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'category_id', 'tax_group_id',
        'name', 'slug', 'description', 'image', 'sku', 'barcode',
        'base_price', 'cost_price', 'type', 'preparation_time', 'unit',
        'is_available', 'is_featured', 'track_inventory', 'sort_order',
        'gallery', 'tags', 'allergens',
    ];

    protected $casts = [
        'base_price'       => 'float',
        'cost_price'       => 'float',
        'preparation_time' => 'integer',
        'is_available'     => 'boolean',
        'is_featured'      => 'boolean',
        'track_inventory'  => 'boolean',
        'gallery'          => 'array',
        'tags'             => 'array',
        'allergens'        => 'array',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function category(): BelongsTo   { return $this->belongsTo(Category::class); }
    public function taxGroup(): BelongsTo   { return $this->belongsTo(TaxGroup::class); }

    public function variants(): HasMany     { return $this->hasMany(MenuItemVariant::class); }
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(MenuItemVariant::class)->where('is_default', true);
    }

    public function recipe(): HasOne        { return $this->hasOne(Recipe::class); }
    public function orderItems(): HasMany   { return $this->hasMany(OrderItem::class); }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'menu_item_modifier_groups')
                    ->withPivot('sort_order')
                    ->orderBy('menu_item_modifier_groups.sort_order');
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeAvailable($query)   { return $query->where('is_available', true); }
    public function scopeFeatured($query)    { return $query->where('is_featured', true); }
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where(fn ($q) =>
            $q->whereNull('branch_id')->orWhere('branch_id', $branchId)
        );
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /** Get effective price for a variant (falls back to base_price) */
    public function getPriceForVariant(?int $variantId): float
    {
        if ($variantId) {
            $variant = $this->variants()->find($variantId);
            if ($variant) return $variant->price;
        }
        return $this->base_price;
    }

    /** Gross profit margin percentage */
    public function getMarginAttribute(): float
    {
        if ($this->base_price <= 0) return 0;
        return round(($this->base_price - $this->cost_price) / $this->base_price * 100, 2);
    }
}
