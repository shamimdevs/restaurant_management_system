<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'promotion_id', 'code', 'description',
        'discount_type', 'discount_value', 'min_order_value', 'max_discount',
        'usage_limit', 'usage_limit_per_customer', 'used_count',
        'valid_from', 'valid_until', 'is_active',
    ];

    protected $casts = [
        'discount_value'             => 'float',
        'min_order_value'            => 'float',
        'max_discount'               => 'float',
        'usage_limit'                => 'integer',
        'usage_limit_per_customer'   => 'integer',
        'used_count'                 => 'integer',
        'valid_from'                 => 'datetime',
        'valid_until'                => 'datetime',
        'is_active'                  => 'boolean',
    ];

    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function promotion(): BelongsTo { return $this->belongsTo(Promotion::class); }
    public function usages(): HasMany      { return $this->hasMany(CouponUsage::class); }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function isValid(?int $customerId = null): bool
    {
        if (! $this->is_active)                                         return false;
        if ($this->valid_from   && now()->lt($this->valid_from))        return false;
        if ($this->valid_until  && now()->gt($this->valid_until))        return false;
        if ($this->usage_limit  && $this->used_count >= $this->usage_limit) return false;
        if ($customerId && $this->usage_limit_per_customer) {
            $used = $this->usages()->where('customer_id', $customerId)->count();
            if ($used >= $this->usage_limit_per_customer) return false;
        }
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < $this->min_order_value) return 0;

        $discount = $this->discount_type === 'percentage'
            ? round($subtotal * $this->discount_value / 100, 2)
            : $this->discount_value;

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return min($discount, $subtotal);
    }
}
