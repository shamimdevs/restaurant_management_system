<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VatReport extends Model
{
    protected $fillable = [
        'branch_id', 'period_start', 'period_end',
        'total_sales', 'taxable_sales', 'exempt_sales',
        'total_vat_collected', 'total_vat_paid', 'net_vat_payable',
        'service_charge_collected', 'status', 'filed_at', 'filed_by', 'filing_reference',
    ];

    protected $casts = [
        'period_start'              => 'date',
        'period_end'                => 'date',
        'total_sales'               => 'float',
        'taxable_sales'             => 'float',
        'exempt_sales'              => 'float',
        'total_vat_collected'       => 'float',
        'total_vat_paid'            => 'float',
        'net_vat_payable'           => 'float',
        'service_charge_collected'  => 'float',
        'filed_at'                  => 'datetime',
    ];

    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
    public function filedBy(): BelongsTo { return $this->belongsTo(User::class, 'filed_by'); }
}
