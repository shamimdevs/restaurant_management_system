<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Promotion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'description', 'image', 'type',
        'value', 'min_order_value', 'max_discount', 'applicable_to',
        'applicable_ids', 'excluded_ids', 'start_date', 'end_date',
        'start_time', 'end_time', 'days_of_week', 'usage_limit',
        'used_count', 'usage_limit_per_customer', 'is_stackable', 'priority', 'is_active',
    ];

    protected $casts = [
        'value'                      => 'float',
        'min_order_value'            => 'float',
        'max_discount'               => 'float',
        'applicable_ids'             => 'array',
        'excluded_ids'               => 'array',
        'days_of_week'               => 'array',
        'start_date'                 => 'date',
        'end_date'                   => 'date',
        'usage_limit'                => 'integer',
        'used_count'                 => 'integer',
        'usage_limit_per_customer'   => 'integer',
        'is_stackable'               => 'boolean',
        'is_active'                  => 'boolean',
    ];

    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function bxgyRules(): HasMany   { return $this->hasMany(PromotionBxgyRule::class); }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', today()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', today()));
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where(fn ($q) =>
            $q->whereNull('branch_id')->orWhere('branch_id', $branchId)
        );
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function isValidNow(): bool
    {
        if (! $this->is_active) return false;

        $now  = Carbon::now('Asia/Dhaka');
        $time = $now->format('H:i:s');
        $day  = $now->dayOfWeek; // 0=Sun

        if ($this->start_date && $now->lt($this->start_date->startOfDay())) return false;
        if ($this->end_date   && $now->gt($this->end_date->endOfDay()))      return false;
        if ($this->start_time && $time < $this->start_time)                  return false;
        if ($this->end_time   && $time > $this->end_time)                    return false;
        if ($this->days_of_week && ! in_array($day, $this->days_of_week))    return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit)   return false;

        return true;
    }

    /** Calculate discount amount for a given subtotal */
    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < $this->min_order_value) return 0;

        $discount = match ($this->type) {
            'percentage_discount' => round($subtotal * $this->value / 100, 2),
            'fixed_discount'      => min($this->value, $subtotal),
            'happy_hour'          => round($subtotal * $this->value / 100, 2),
            default               => 0,
        };

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return max(0, $discount);
    }
}
