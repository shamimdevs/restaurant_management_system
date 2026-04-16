<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlert extends Model
{
    protected $fillable = [
        'branch_id', 'ingredient_id', 'alert_type', 'current_quantity',
        'threshold_quantity', 'is_resolved', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'current_quantity'   => 'float',
        'threshold_quantity' => 'float',
        'is_resolved'        => 'boolean',
        'resolved_at'        => 'datetime',
    ];

    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
    public function resolvedBy(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }

    public function scopeUnresolved($query) { return $query->where('is_resolved', false); }

    public function resolve(int $userId): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_by' => $userId,
            'resolved_at' => now(),
        ]);
    }
}
