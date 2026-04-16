<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'phone', 'email', 'gender',
        'date_of_birth', 'anniversary_date', 'address', 'area', 'city',
        'avatar', 'loyalty_points', 'total_spent', 'visit_count',
        'last_visit_at', 'segment', 'notes', 'is_active',
    ];

    protected $casts = [
        'date_of_birth'    => 'date',
        'anniversary_date' => 'date',
        'last_visit_at'    => 'datetime',
        'loyalty_points'   => 'integer',
        'total_spent'      => 'float',
        'visit_count'      => 'integer',
        'is_active'        => 'boolean',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    public function company(): BelongsTo     { return $this->belongsTo(Company::class); }
    public function addresses(): HasMany     { return $this->hasMany(CustomerAddress::class); }
    public function orders(): HasMany        { return $this->hasMany(Order::class); }
    public function pointTransactions(): HasMany { return $this->hasMany(CustomerPointTransaction::class); }
    public function feedback(): HasMany      { return $this->hasMany(CustomerFeedback::class); }

    public function defaultAddress(): HasMany
    {
        return $this->addresses()->where('is_default', true);
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)       { return $query->where('is_active', true); }
    public function scopeVip($query)          { return $query->where('segment', 'vip'); }
    public function scopeByPhone($query, string $phone) { return $query->where('phone', $phone); }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function recordVisit(float $orderAmount): void
    {
        $this->increment('visit_count');
        $this->increment('total_spent', $orderAmount);
        $this->update(['last_visit_at' => now()]);
        $this->updateSegment();
    }

    public function updateSegment(): void
    {
        $segment = match (true) {
            $this->total_spent >= 50000 || $this->loyalty_points >= 5000 => 'vip',
            $this->visit_count >= 5                                       => 'regular',
            default                                                        => 'new',
        };
        $this->update(['segment' => $segment]);
    }

    public function addPoints(int $points, string $description = '', ?int $orderId = null): void
    {
        $this->increment('loyalty_points', $points);
        $this->pointTransactions()->create([
            'company_id'    => $this->company_id,
            'order_id'      => $orderId,
            'type'          => 'earned',
            'points'        => $points,
            'balance_after' => $this->loyalty_points,
            'description'   => $description,
        ]);
    }

    public function redeemPoints(int $points, ?int $orderId = null): bool
    {
        if ($this->loyalty_points < $points) return false;

        $this->decrement('loyalty_points', $points);
        $this->pointTransactions()->create([
            'company_id'    => $this->company_id,
            'order_id'      => $orderId,
            'type'          => 'redeemed',
            'points'        => -$points,
            'balance_after' => $this->loyalty_points,
            'description'   => 'Points redeemed',
        ]);
        return true;
    }
}
