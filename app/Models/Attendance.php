<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'branch_id', 'date', 'check_in', 'check_out',
        'hours_worked', 'overtime_hours', 'status', 'check_in_method', 'note',
    ];

    protected $casts = [
        'date'           => 'date',
        'hours_worked'   => 'float',
        'overtime_hours' => 'float',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }

    public function scopePresent($query) { return $query->whereIn('status', ['present', 'late', 'half_day']); }
    public function scopeMonth($query, int $month, int $year)
    {
        return $query->whereMonth('date', $month)->whereYear('date', $year);
    }
}
