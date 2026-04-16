<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'email', 'phone',
        'password', 'avatar', 'pin', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token', 'pin'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withPivot('branch_id')
                    ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function employee(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    // ── Permission Checks ───────────────────────────────────────────────

    /**
     * Check if user has a specific permission (optionally scoped to a branch).
     */
    public function hasPermission(string $permission, ?int $branchId = null): bool
    {
        return $this->roles()
            ->when($branchId, fn ($q) => $q->where(function ($q) use ($branchId) {
                $q->whereNull('user_roles.branch_id')
                  ->orWhere('user_roles.branch_id', $branchId);
            }))
            ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
            ->exists();
    }

    /**
     * Check if user has a role slug.
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSuperAdmin(): bool
    {
        // Super admin has no branch restriction
        return $this->isAdmin() && is_null($this->branch_id);
    }

    /**
     * Get all permission names for this user (cached per request).
     */
    public function allPermissions(): array
    {
        return once(function () {
            return $this->roles()
                ->with('permissions')
                ->get()
                ->flatMap(fn ($r) => $r->permissions->pluck('name'))
                ->unique()
                ->values()
                ->all();
        });
    }
}
