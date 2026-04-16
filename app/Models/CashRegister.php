<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegister extends Model
{
    protected $fillable = [
        'branch_id', 'opened_by', 'closed_by', 'opening_balance',
        'closing_balance', 'expected_balance', 'difference',
        'opened_at', 'closed_at', 'status', 'notes',
    ];

    protected $casts = [
        'opening_balance'  => 'float',
        'closing_balance'  => 'float',
        'expected_balance' => 'float',
        'difference'       => 'float',
        'opened_at'        => 'datetime',
        'closed_at'        => 'datetime',
    ];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function openedBy(): BelongsTo  { return $this->belongsTo(User::class, 'opened_by'); }
    public function closedBy(): BelongsTo  { return $this->belongsTo(User::class, 'closed_by'); }

    public function scopeOpen($query)      { return $query->where('status', 'open'); }

    /** Calculate expected balance from cash orders + opening */
    public function calculateExpectedBalance(): float
    {
        $cashSales = Order::where('branch_id', $this->branch_id)
            ->whereDate('created_at', $this->opened_at->toDateString())
            ->whereHas('payments', fn ($q) => $q->where('payment_method', 'cash')->where('status', 'completed'))
            ->with('payments')
            ->get()
            ->sum(fn ($o) => $o->payments->where('payment_method', 'cash')->sum('amount'));

        return $this->opening_balance + $cashSales;
    }
}
