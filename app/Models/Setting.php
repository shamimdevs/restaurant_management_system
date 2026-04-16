<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = ['company_id', 'branch_id', 'group', 'key', 'value', 'type', 'description'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }

    /** Return value cast to the correct PHP type */
    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float'   => (float) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    /** Retrieve a setting value with a default fallback */
    public static function get(int $companyId, string $key, mixed $default = null, ?int $branchId = null): mixed
    {
        $query = static::where('company_id', $companyId)->where('key', $key);

        if ($branchId) {
            $setting = (clone $query)->where('branch_id', $branchId)->first()
                    ?? $query->whereNull('branch_id')->first();
        } else {
            $setting = $query->whereNull('branch_id')->first();
        }

        return $setting ? $setting->typed_value : $default;
    }

    /** Upsert a setting value */
    public static function set(int $companyId, string $key, mixed $value, string $type = 'string', ?int $branchId = null): void
    {
        static::updateOrCreate(
            ['company_id' => $companyId, 'branch_id' => $branchId, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'type' => $type]
        );
    }
}
