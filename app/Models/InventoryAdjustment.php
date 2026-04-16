<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'branch_id', 'reference_no', 'status', 'reason', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = ['approved_at' => 'datetime'];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany       { return $this->hasMany(InventoryAdjustmentItem::class); }
}
