<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = ['company_id', 'name', 'days_allowed_per_year', 'is_paid'];
    protected $casts    = ['is_paid' => 'boolean'];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function requests(): HasMany   { return $this->hasMany(LeaveRequest::class); }
}
