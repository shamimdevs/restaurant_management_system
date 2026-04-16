<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $fillable = ['company_id', 'account_id', 'name', 'description', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function expenses(): HasMany  { return $this->hasMany(Expense::class); }
}
