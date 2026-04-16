<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    protected $fillable = [
        'branch_id', 'employee_id', 'name', 'phone', 'vehicle_type', 'vehicle_number', 'status', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function orders(): HasMany      { return $this->hasMany(Order::class); }

    public function scopeAvailable($query) { return $query->where('status', 'available')->where('is_active', true); }
}
