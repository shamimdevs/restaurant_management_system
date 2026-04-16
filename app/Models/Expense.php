<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'expense_category_id', 'journal_entry_id', 'reference_no',
        'amount', 'vat_amount', 'description', 'paid_to', 'receipt_image',
        'expense_date', 'payment_method', 'status', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'amount'      => 'float',
        'vat_amount'  => 'float',
        'expense_date'=> 'date',
        'approved_at' => 'datetime',
    ];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function category(): BelongsTo  { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('expense_date', [$from, $to]);
    }
}
