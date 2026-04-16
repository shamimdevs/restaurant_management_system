<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    protected $fillable = ['user_id', 'role_id', 'branch_id'];

    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function role(): BelongsTo  { return $this->belongsTo(Role::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
