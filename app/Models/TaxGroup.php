<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxGroup extends Model
{
    protected $fillable = ['company_id', 'name', 'description', 'is_default', 'is_active'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    public function rates(): HasMany { return $this->hasMany(TaxRate::class); }

    public function menuItems(): HasMany { return $this->hasMany(MenuItem::class); }

    /** Calculate combined tax rate for a given amount */
    public function calculateTax(float $amount): array
    {
        $breakdown = [];
        $totalTax  = 0;

        foreach ($this->rates()->where('is_active', true)->get() as $rate) {
            $tax = $rate->is_inclusive
                ? round($amount - ($amount / (1 + $rate->rate / 100)), 2)
                : round($amount * $rate->rate / 100, 2);

            $breakdown[] = [
                'tax_rate_id'    => $rate->id,
                'tax_name'       => $rate->name,
                'tax_rate'       => $rate->rate,
                'taxable_amount' => $amount,
                'tax_amount'     => $tax,
            ];
            $totalTax += $tax;
        }

        return ['breakdown' => $breakdown, 'total_tax' => $totalTax];
    }
}
