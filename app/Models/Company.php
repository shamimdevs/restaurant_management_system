<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'logo', 'address', 'city', 'phone', 'email',
        'website', 'registration_no', 'vat_registration_no', 'trade_license_no',
        'currency', 'currency_symbol', 'timezone', 'date_format', 'time_format',
        'fiscal_year_start', 'receipt_settings', 'notification_settings', 'is_active',
    ];

    protected $casts = [
        'receipt_settings'      => 'array',
        'notification_settings' => 'array',
        'is_active'             => 'boolean',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function taxGroups(): HasMany
    {
        return $this->hasMany(TaxGroup::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function loyaltyPrograms(): HasMany
    {
        return $this->hasMany(LoyaltyProgram::class);
    }

    // ── Accessors ───────────────────────────────────────────────────────

    public function getActiveBranchesAttribute()
    {
        return $this->branches()->where('is_active', true)->get();
    }
}
