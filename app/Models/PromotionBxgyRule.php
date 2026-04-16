<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionBxgyRule extends Model
{
    protected $fillable = [
        'promotion_id', 'buy_menu_item_id', 'buy_quantity',
        'get_menu_item_id', 'get_quantity', 'get_discount_percentage',
    ];

    protected $casts = ['get_discount_percentage' => 'float'];

    public function promotion(): BelongsTo  { return $this->belongsTo(Promotion::class); }
    public function buyItem(): BelongsTo    { return $this->belongsTo(MenuItem::class, 'buy_menu_item_id'); }
    public function getItem(): BelongsTo    { return $this->belongsTo(MenuItem::class, 'get_menu_item_id'); }
}
