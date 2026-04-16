<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id', 'leave_type_id', 'from_date', 'to_date', 'days',
        'reason', 'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'from_date'   => 'date',
        'to_date'     => 'date',
        'days'        => 'integer',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }
    public function approvedBy(): BelongsTo{ return $this->belongsTo(User::class, 'approved_by'); }
}
