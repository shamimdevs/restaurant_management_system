<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRate extends Model
{
    protected $fillable = ['tax_group_id', 'name', 'type', 'rate', 'is_inclusive', 'sort_order', 'is_active'];

    protected $casts = [
        'rate'         => 'float',
        'is_inclusive' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function taxGroup(): BelongsTo { return $this->belongsTo(TaxGroup::class); }
}
