<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'branch_id', 'company_id', 'table_session_id',
        'customer_id', 'user_id', 'coupon_id', 'order_type', 'status',
        'payment_status', 'subtotal', 'discount_amount', 'discount_label',
        'coupon_discount', 'vat_amount', 'service_charge', 'delivery_fee',
        'total_amount', 'paid_amount', 'change_amount',
        'loyalty_points_used', 'loyalty_points_earned',
        'delivery_address', 'delivery_area', 'rider_id',
        'estimated_delivery_at', 'delivered_at', 'is_split', 'parent_order_id',
        'source', 'notes', 'cancel_reason',
        'confirmed_at', 'completed_at', 'cancelled_at',
    ];

    protected $casts = [
        'subtotal'              => 'float',
        'discount_amount'       => 'float',
        'coupon_discount'       => 'float',
        'vat_amount'            => 'float',
        'service_charge'        => 'float',
        'delivery_fee'          => 'float',
        'total_amount'          => 'float',
        'paid_amount'           => 'float',
        'change_amount'         => 'float',
        'loyalty_points_used'   => 'float',
        'loyalty_points_earned' => 'integer',
        'is_split'              => 'boolean',
        'confirmed_at'          => 'datetime',
        'completed_at'          => 'datetime',
        'cancelled_at'          => 'datetime',
        'estimated_delivery_at' => 'datetime',
        'delivered_at'          => 'datetime',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    public function branch(): BelongsTo        { return $this->belongsTo(Branch::class); }
    public function company(): BelongsTo       { return $this->belongsTo(Company::class); }
    public function tableSession(): BelongsTo  { return $this->belongsTo(TableSession::class); }
    public function customer(): BelongsTo      { return $this->belongsTo(Customer::class); }
    public function user(): BelongsTo          { return $this->belongsTo(User::class); }
    public function coupon(): BelongsTo        { return $this->belongsTo(Coupon::class); }
    public function rider(): BelongsTo         { return $this->belongsTo(Rider::class); }
    public function parentOrder(): BelongsTo   { return $this->belongsTo(Order::class, 'parent_order_id'); }

    public function items(): HasMany           { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany        { return $this->hasMany(OrderPayment::class); }
    public function taxDetails(): HasMany      { return $this->hasMany(OrderTaxDetail::class); }
    public function splitOrders(): HasMany     { return $this->hasMany(Order::class, 'parent_order_id'); }
    public function kitchenTicket(): HasOne    { return $this->hasOne(KitchenTicket::class); }
    public function feedback(): HasOne         { return $this->hasOne(CustomerFeedback::class); }
    public function pointTransactions(): HasMany { return $this->hasMany(CustomerPointTransaction::class); }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopePending($query)    { return $query->where('status', 'pending'); }
    public function scopeCompleted($query)  { return $query->where('status', 'completed'); }
    public function scopeToday($query)      { return $query->whereDate('created_at', today()); }
    public function scopeForBranch($query, int $branchId) { return $query->where('branch_id', $branchId); }

    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
    }

    // ── Status Helpers ───────────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }
    public function isPaid(): bool       { return $this->payment_status === 'paid'; }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed', 'confirmed_at' => now()]);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed', 'completed_at' => now()]);
    }

    public function cancel(string $reason = ''): void
    {
        $this->update([
            'status'        => 'cancelled',
            'cancel_reason' => $reason,
            'cancelled_at'  => now(),
        ]);
    }

    // ── Accessors ────────────────────────────────────────────────────────

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->paid_amount >= $this->total_amount;
    }

    /** Generate the next order number for a branch */
    public static function generateNumber(int $branchId): string
    {
        $branch = Branch::find($branchId);
        $prefix = 'ORD-' . strtoupper($branch->code) . '-' . now()->format('Ymd');
        $last   = static::where('branch_id', $branchId)
                         ->whereDate('created_at', today())
                         ->lockForUpdate()
                         ->count();
        return $prefix . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
