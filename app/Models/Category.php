<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'parent_id', 'name', 'slug',
        'description', 'image', 'color', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function parent(): BelongsTo   { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children(): HasMany   { return $this->hasMany(Category::class, 'parent_id'); }
    public function menuItems(): HasMany  { return $this->hasMany(MenuItem::class); }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)     { return $query->where('is_active', true); }
    public function scopeRootOnly($query)   { return $query->whereNull('parent_id'); }
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where(fn ($q) =>
            $q->whereNull('branch_id')->orWhere('branch_id', $branchId)
        );
    }
}
