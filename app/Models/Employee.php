<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'branch_id', 'department_id', 'designation_id', 'employee_id',
        'name', 'phone', 'email', 'nid_number', 'address', 'area', 'city',
        'gender', 'date_of_birth', 'joining_date', 'end_date', 'photo',
        'emergency_contact', 'salary_type', 'basic_salary', 'bank_account', 'status',
    ];

    protected $casts = [
        'emergency_contact' => 'array',
        'bank_account'      => 'array',
        'date_of_birth'     => 'date',
        'joining_date'      => 'date',
        'end_date'          => 'date',
        'basic_salary'      => 'float',
    ];

    protected $hidden = ['bank_account', 'nid_number'];

    protected $appends = ['role', 'is_active'];

    public function user(): BelongsTo          { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo        { return $this->belongsTo(Branch::class); }
    public function department(): BelongsTo    { return $this->belongsTo(Department::class); }
    public function designation(): BelongsTo   { return $this->belongsTo(Designation::class); }
    public function attendance(): HasMany      { return $this->hasMany(Attendance::class); }
    public function leaveRequests(): HasMany   { return $this->hasMany(LeaveRequest::class); }
    public function salaryPayments(): HasMany  { return $this->hasMany(SalaryPayment::class); }
    public function currentSalaryStructure(): HasOne
    {
        return $this->hasOne(SalaryStructure::class)->where('is_current', true)->latest();
    }

    public function scopeActive($query) { return $query->where('status', 'active'); }

    /** Frontend uses emp.role to display role badge */
    public function getRoleAttribute(): string
    {
        return $this->designation?->name ?? $this->department?->name ?? 'Staff';
    }

    /** Frontend uses emp.is_active */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getTodayAttendanceAttribute(): ?Attendance
    {
        return $this->attendance()->whereDate('date', today())->first();
    }

    public function getServiceDurationAttribute(): string
    {
        return $this->joining_date->diffForHumans(now(), true);
    }
}
