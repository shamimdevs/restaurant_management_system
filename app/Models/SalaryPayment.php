<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    protected $fillable = [
        'employee_id', 'branch_id', 'salary_structure_id', 'payroll_reference',
        'month', 'year', 'basic_salary', 'total_allowances', 'total_deductions',
        'overtime_pay', 'bonus', 'tax_deduction', 'net_salary',
        'working_days', 'present_days', 'payment_date',
        'payment_method', 'payment_reference', 'notes', 'status', 'paid_by',
    ];

    protected $casts = [
        'month'             => 'integer',
        'year'              => 'integer',
        'basic_salary'      => 'float',
        'total_allowances'  => 'float',
        'total_deductions'  => 'float',
        'overtime_pay'      => 'float',
        'bonus'             => 'float',
        'tax_deduction'     => 'float',
        'net_salary'        => 'float',
        'working_days'      => 'integer',
        'present_days'      => 'integer',
        'payment_date'      => 'date',
    ];

    public function employee(): BelongsTo        { return $this->belongsTo(Employee::class); }
    public function branch(): BelongsTo          { return $this->belongsTo(Branch::class); }
    public function salaryStructure(): BelongsTo { return $this->belongsTo(SalaryStructure::class); }
    public function paidBy(): BelongsTo          { return $this->belongsTo(User::class, 'paid_by'); }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }
}
