<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'branch_id', 'user_id', 'action', 'model_type', 'model_id',
        'description', 'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }

    public static function record(string $action, string $description, array $context = []): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        if (! $user) return;

        static::create(array_merge([
            'company_id'  => $user->company_id,
            'branch_id'   => $user->branch_id,
            'user_id'     => $user->id,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'created_at'  => now(),
        ], $context));
    }
}
