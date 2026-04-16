<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyProgram extends Model
{
    protected $fillable = [
        'company_id', 'name', 'points_per_currency', 'currency_per_point',
        'min_redeem_points', 'redeem_value', 'min_order_for_earn',
        'point_expiry_days', 'is_active',
    ];

    protected $casts = [
        'points_per_currency'  => 'float',
        'currency_per_point'   => 'float',
        'min_redeem_points'    => 'integer',
        'redeem_value'         => 'float',
        'min_order_for_earn'   => 'float',
        'point_expiry_days'    => 'integer',
        'is_active'            => 'boolean',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    /** Calculate points to earn for a given order amount */
    public function calculateEarnPoints(float $orderAmount): int
    {
        if ($orderAmount < $this->min_order_for_earn) return 0;
        return (int) floor($orderAmount / $this->currency_per_point);
    }

    /** Calculate BDT value of redeemable points */
    public function calculateRedeemValue(int $points): float
    {
        if ($points < $this->min_redeem_points) return 0;
        return round($points * $this->redeem_value / 100, 2);
    }
}
