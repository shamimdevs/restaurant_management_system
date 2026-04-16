<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'supplier_id', 'po_number', 'status',
        'order_date', 'expected_date', 'received_date',
        'subtotal', 'vat_amount', 'other_charges', 'total_amount',
        'paid_amount', 'payment_status', 'notes', 'created_by', 'received_by',
    ];

    protected $casts = [
        'order_date'    => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'subtotal'      => 'float',
        'vat_amount'    => 'float',
        'other_charges' => 'float',
        'total_amount'  => 'float',
        'paid_amount'   => 'float',
    ];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function supplier(): BelongsTo  { return $this->belongsTo(Supplier::class); }
    public function items(): HasMany       { return $this->hasMany(PurchaseOrderItem::class); }
    public function payments(): HasMany    { return $this->hasMany(SupplierPayment::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function receivedBy(): BelongsTo{ return $this->belongsTo(User::class, 'received_by'); }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public static function generatePoNumber(int $branchId): string
    {
        $branch = Branch::find($branchId);
        $last   = static::where('branch_id', $branchId)->count();
        return 'PO-' . strtoupper($branch->code) . '-' . now()->format('Ymd') . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
