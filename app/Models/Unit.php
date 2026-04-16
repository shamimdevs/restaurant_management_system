<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    protected $fillable = ['company_id', 'name', 'abbreviation', 'type', 'is_base_unit'];
    protected $casts    = ['is_base_unit' => 'boolean'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
