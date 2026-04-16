<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountGroup extends Model
{
    protected $fillable = [
        'company_id', 'parent_id', 'name', 'code', 'type', 'normal_balance', 'is_system', 'sort_order',
    ];

    protected $casts = ['is_system' => 'boolean'];

    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function parent(): BelongsTo    { return $this->belongsTo(AccountGroup::class, 'parent_id'); }
    public function children(): HasMany    { return $this->hasMany(AccountGroup::class, 'parent_id'); }
    public function accounts(): HasMany    { return $this->hasMany(Account::class); }
}
