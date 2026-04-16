<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\InventoryAdjustment;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Deduct ingredients for all items in a completed order.
     * Uses recipe-based deduction.
     */
    public function deductForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $orderItem) {
                $recipe = $orderItem->menuItem->recipe;
                if (! $recipe) continue;

                $servings = $orderItem->quantity / $recipe->yield_quantity;

                foreach ($recipe->ingredients as $ri) {
                    $ingredient = Ingredient::where('branch_id', $order->branch_id)
                                            ->where('id', $ri->ingredient_id)
                                            ->lockForUpdate()
                                            ->first();

                    if (! $ingredient || ! $ingredient->track_stock) continue;

                    $qtyToDeduct = round($ri->effective_quantity * $servings, 4);
                    $ingredient->deductStock($qtyToDeduct, Order::class, $order->id, $order->user_id ?? 1);
                }
            }
        });
    }

    /**
     * Receive stock from a purchase order.
     */
    public function receiveStock(PurchaseOrder $po, array $received, int $userId): void
    {
        DB::transaction(function () use ($po, $received, $userId) {
            foreach ($received as $item) {
                $poItem = $po->items()->find($item['purchase_order_item_id']);
                if (! $poItem) continue;

                $ingredient = Ingredient::where('branch_id', $po->branch_id)
                                        ->where('id', $poItem->ingredient_id)
                                        ->lockForUpdate()
                                        ->first();

                if (! $ingredient) continue;

                $qty    = (float) $item['quantity'];
                $before = $ingredient->current_stock;

                $ingredient->increment('current_stock', $qty);
                $ingredient->update(['cost_per_unit' => $poItem->unit_price]); // update FIFO cost

                StockMovement::create([
                    'branch_id'      => $po->branch_id,
                    'ingredient_id'  => $ingredient->id,
                    'unit_id'        => $poItem->unit_id,
                    'type'           => 'purchase',
                    'quantity'       => $qty,
                    'unit_cost'      => $poItem->unit_price,
                    'total_cost'     => round($qty * $poItem->unit_price, 2),
                    'stock_before'   => $before,
                    'stock_after'    => $ingredient->fresh()->current_stock,
                    'reference_type' => PurchaseOrder::class,
                    'reference_id'   => $po->id,
                    'user_id'        => $userId,
                    'notes'          => "Received via PO #{$po->po_number}",
                    'movement_date'  => today(),
                ]);

                $poItem->increment('received_quantity', $qty);
            }

            // Update PO status
            $allReceived = $po->items->every(fn ($i) => $i->received_quantity >= $i->quantity);
            $po->update([
                'status'        => $allReceived ? 'received' : 'partial',
                'received_date' => now(),
            ]);
        });
    }

    /**
     * Apply an approved inventory adjustment.
     */
    public function applyAdjustment(InventoryAdjustment $adjustment, int $userId): void
    {
        DB::transaction(function () use ($adjustment, $userId) {
            foreach ($adjustment->items as $adjItem) {
                $ingredient = Ingredient::where('branch_id', $adjustment->branch_id)
                                        ->where('id', $adjItem->ingredient_id)
                                        ->lockForUpdate()
                                        ->first();

                if (! $ingredient) continue;

                $diff   = $adjItem->actual_quantity - $ingredient->current_stock;
                $before = $ingredient->current_stock;

                $ingredient->update(['current_stock' => $adjItem->actual_quantity]);

                StockMovement::create([
                    'branch_id'      => $adjustment->branch_id,
                    'ingredient_id'  => $ingredient->id,
                    'unit_id'        => $ingredient->unit_id,
                    'type'           => 'adjustment',
                    'quantity'       => $diff,
                    'unit_cost'      => $ingredient->cost_per_unit,
                    'total_cost'     => round(abs($diff) * $ingredient->cost_per_unit, 2),
                    'stock_before'   => $before,
                    'stock_after'    => $adjItem->actual_quantity,
                    'reference_type' => InventoryAdjustment::class,
                    'reference_id'   => $adjustment->id,
                    'user_id'        => $userId,
                    'notes'          => $adjustment->reason,
                    'movement_date'  => today(),
                ]);
            }

            $adjustment->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
        });
    }

    /**
     * Get current stock value summary for a branch.
     */
    public function getStockSummary(int $branchId): array
    {
        $ingredients = Ingredient::where('branch_id', $branchId)->where('is_active', true)->get();

        return [
            'total_items'       => $ingredients->count(),
            'total_value'       => $ingredients->sum('stock_value'),
            'low_stock_count'   => $ingredients->filter->isLowStock()->count(),
            'out_of_stock_count'=> $ingredients->filter->isOutOfStock()->count(),
        ];
    }
}
