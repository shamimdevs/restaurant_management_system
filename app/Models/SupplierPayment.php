<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    protected $fillable = [
        'supplier_id', 'branch_id', 'purchase_order_id',
        'amount', 'payment_method', 'reference', 'notes', 'paid_date', 'paid_by',
    ];

    protected $casts = ['amount' => 'float', 'paid_date' => 'date'];

    public function supplier(): BelongsTo      { return $this->belongsTo(Supplier::class); }
    public function branch(): BelongsTo        { return $this->belongsTo(Branch::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function paidBy(): BelongsTo        { return $this->belongsTo(User::class, 'paid_by'); }
}
