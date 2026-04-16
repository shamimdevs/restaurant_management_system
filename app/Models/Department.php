<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['branch_id', 'name'];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function employees(): HasMany   { return $this->hasMany(Employee::class); }
}
