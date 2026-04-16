<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    protected $fillable = [
        'employee_id', 'basic_salary', 'allowances', 'deductions',
        'gross_salary', 'net_salary', 'effective_from', 'effective_to', 'is_current',
    ];

    protected $casts = [
        'basic_salary'   => 'float',
        'gross_salary'   => 'float',
        'net_salary'     => 'float',
        'allowances'     => 'array',
        'deductions'     => 'array',
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_current'     => 'boolean',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }

    public function getTotalAllowancesAttribute(): float
    {
        return collect($this->allowances)->sum('amount');
    }

    public function getTotalDeductionsAttribute(): float
    {
        return collect($this->deductions)->sum('amount');
    }
}
