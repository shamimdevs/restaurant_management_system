<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'code', 'address', 'area', 'city', 'phone', 'email',
        'latitude', 'longitude', 'opening_time', 'closing_time', 'working_days',
        'manager_id', 'table_count', 'settings', 'is_active',
    ];

    protected $casts = [
        'working_days' => 'array',
        'settings'     => 'array',
        'is_active'    => 'boolean',
        'latitude'     => 'float',
        'longitude'    => 'float',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function riders(): HasMany
    {
        return $this->hasMany(Rider::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    public function getOpenCashRegister(): ?CashRegister
    {
        return $this->cashRegisters()->where('status', 'open')->latest()->first();
    }
}
