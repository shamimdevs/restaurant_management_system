<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['company_id', 'name', 'slug', 'description', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
                    ->withPivot('branch_id')
                    ->withTimestamps();
    }

    public function givePermission(string|array $permissions): void
    {
        $ids = Permission::whereIn('name', (array) $permissions)->pluck('id');
        $this->permissions()->syncWithoutDetaching($ids);
    }

    public function revokePermission(string|array $permissions): void
    {
        $ids = Permission::whereIn('name', (array) $permissions)->pluck('id');
        $this->permissions()->detach($ids);
    }
}
