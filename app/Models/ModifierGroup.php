<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModifierGroup extends Model
{
    protected $fillable = [
        'company_id', 'name', 'min_selections', 'max_selections',
        'is_required', 'sort_order',
    ];

    protected $casts = ['is_required' => 'boolean'];

    public function company(): BelongsTo     { return $this->belongsTo(Company::class); }
    public function modifiers(): HasMany     { return $this->hasMany(Modifier::class)->orderBy('sort_order'); }

    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'menu_item_modifier_groups')
                    ->withPivot('sort_order');
    }
}
