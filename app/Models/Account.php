<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'company_id', 'account_group_id', 'name', 'code', 'description',
        'type', 'opening_balance', 'current_balance', 'is_system', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'current_balance' => 'float',
        'is_system'       => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function company(): BelongsTo      { return $this->belongsTo(Company::class); }
    public function group(): BelongsTo        { return $this->belongsTo(AccountGroup::class, 'account_group_id'); }
    public function journalLines(): HasMany   { return $this->hasMany(JournalEntryLine::class); }

    /** Recalculate running balance from journal lines */
    public function recalculateBalance(): void
    {
        $debitTotal  = $this->journalLines()->sum('debit');
        $creditTotal = $this->journalLines()->sum('credit');

        $balance = match ($this->type) {
            'asset', 'expense' => $this->opening_balance + $debitTotal - $creditTotal,
            default            => $this->opening_balance + $creditTotal - $debitTotal,
        };

        $this->update(['current_balance' => round($balance, 2)]);
    }
}
