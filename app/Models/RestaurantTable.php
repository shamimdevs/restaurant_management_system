<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RestaurantTable extends Model
{
    protected $fillable = [
        'branch_id', 'floor_plan_id', 'table_number', 'name',
        'capacity', 'shape', 'status', 'qr_code', 'qr_image_path', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean', 'capacity' => 'integer'];

    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function floorPlan(): BelongsTo  { return $this->belongsTo(FloorPlan::class); }
    public function sessions(): HasMany     { return $this->hasMany(TableSession::class); }
    public function reservations(): HasMany { return $this->hasMany(Reservation::class); }

    public function activeSession(): HasOne
    {
        return $this->hasOne(TableSession::class)->where('status', 'active');
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeAvailable($query)  { return $query->where('status', 'available'); }
    public function scopeActive($query)     { return $query->where('is_active', true); }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function isAvailable(): bool   { return $this->status === 'available'; }
    public function isOccupied(): bool    { return $this->status === 'occupied'; }

    public function getQrUrl(): string
    {
        return route('qr.order', ['code' => $this->qr_code]);
    }

    public function markOccupied(): void
    {
        $this->update(['status' => 'occupied']);
    }

    public function markAvailable(): void
    {
        $this->update(['status' => 'available']);
    }
}
