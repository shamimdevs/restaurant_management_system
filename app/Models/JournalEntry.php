<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'entry_number', 'entry_date', 'description',
        'reference_type', 'reference_id', 'status', 'total_debit', 'total_credit',
        'created_by', 'posted_by', 'posted_at',
    ];

    protected $casts = [
        'entry_date'   => 'date',
        'total_debit'  => 'float',
        'total_credit' => 'float',
        'posted_at'    => 'datetime',
    ];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function lines(): HasMany        { return $this->hasMany(JournalEntryLine::class); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function postedBy(): BelongsTo   { return $this->belongsTo(User::class, 'posted_by'); }
    public function reference(): MorphTo    { return $this->morphTo(); }

    public function isBalanced(): bool
    {
        return round($this->lines->sum('debit'), 2) === round($this->lines->sum('credit'), 2);
    }

    public static function generateEntryNumber(int $companyId): string
    {
        $last = static::where('company_id', $companyId)->count();
        return 'JE-' . now()->format('Y') . '-' . str_pad($last + 1, 6, '0', STR_PAD_LEFT);
    }
}
