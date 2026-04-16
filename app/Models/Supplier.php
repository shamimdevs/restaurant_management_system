<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'contact_person', 'phone', 'email',
        'address', 'area', 'city', 'vat_registration_no', 'bank_details',
        'balance', 'credit_days', 'is_active', 'notes',
    ];

    protected $casts = ['bank_details' => 'array', 'balance' => 'float', 'is_active' => 'boolean'];

    public function company(): BelongsTo       { return $this->belongsTo(Company::class); }
    public function purchaseOrders(): HasMany  { return $this->hasMany(PurchaseOrder::class); }
    public function payments(): HasMany        { return $this->hasMany(SupplierPayment::class); }
}
