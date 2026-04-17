<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\KitchenTicket;
use App\Models\KitchenTicketItem;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\OrderTaxDetail;
use App\Models\Promotion;
use App\Models\TableSession;
use App\Models\TaxGroup;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly LoyaltyService  $loyaltyService,
        private readonly AccountingService $accountingService,
    ) {}

    /**
     * Place a new order (POS, QR, or Delivery).
     *
     * $data = [
     *   'branch_id', 'order_type', 'table_session_id'?, 'customer_id'?,
     *   'coupon_code'?, 'loyalty_redeem_points'?,
     *   'notes'?, 'delivery_address'?, 'delivery_fee'?,
     *   'items' => [
     *     ['menu_item_id', 'variant_id'?, 'quantity', 'notes'?,
     *      'modifiers' => [['modifier_id', 'quantity'?]]]
     *   ],
     *   'payments' => [['method', 'amount', 'reference'?]]
     * ]
     */
    public function placeOrder(array $data, int $userId): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            // ── 1. Build item lines ─────────────────────────────────────
            $itemLines = $this->buildItemLines($data['items'], $data['branch_id']);

            // ── 2. Calculate subtotal ───────────────────────────────────
            $subtotal = collect($itemLines)->sum('subtotal');

            // ── 3. Apply promotions ─────────────────────────────────────
            [$discountAmount, $discountLabel] = $this->applyPromotions(
                $subtotal, $data['branch_id'], $data['order_type']
            );

            // ── 4. Apply coupon ─────────────────────────────────────────
            $coupon         = null;
            $couponDiscount = 0;
            if (! empty($data['coupon_code'])) {
                $coupon         = Coupon::where('code', $data['coupon_code'])->first();
                $couponDiscount = $coupon ? $coupon->calculateDiscount($subtotal - $discountAmount) : 0;
            }

            // ── 5. Calculate VAT per item ───────────────────────────────
            [$taxBreakdown, $totalVat, $totalServiceCharge] = $this->calculateTaxes($itemLines, $subtotal - $discountAmount - $couponDiscount);

            // ── 6. Loyalty points redemption ────────────────────────────
            $loyaltyDiscount = 0;
            if (! empty($data['loyalty_redeem_points']) && ! empty($data['customer_id'])) {
                $loyaltyDiscount = $this->loyaltyService->calculateRedeemValue(
                    $data['customer_id'],
                    $data['loyalty_redeem_points'],
                    $data['branch_id']
                );
            }

            // ── 7. Compute total ────────────────────────────────────────
            $deliveryFee = (float) ($data['delivery_fee'] ?? 0);
            $totalAmount = round(
                $subtotal - $discountAmount - $couponDiscount - $loyaltyDiscount
                + $totalVat + $totalServiceCharge + $deliveryFee,
                2
            );

            // ── 8. Create Order ─────────────────────────────────────────
            $order = Order::create([
                'order_number'          => Order::generateNumber($data['branch_id']),
                'branch_id'             => $data['branch_id'],
                'company_id'            => $data['company_id'],
                'table_session_id'      => $data['table_session_id'] ?? null,
                'customer_id'           => $data['customer_id'] ?? null,
                'user_id'               => $userId,
                'coupon_id'             => $coupon?->id,
                'order_type'            => $data['order_type'],
                'status'                => 'confirmed',
                'payment_status'        => 'unpaid',
                'subtotal'              => $subtotal,
                'discount_amount'       => $discountAmount,
                'discount_label'        => $discountLabel,
                'coupon_discount'       => $couponDiscount,
                'vat_amount'            => $totalVat,
                'service_charge'        => $totalServiceCharge,
                'delivery_fee'          => $deliveryFee,
                'total_amount'          => $totalAmount,
                'loyalty_points_used'   => $loyaltyDiscount,
                'delivery_address'      => $data['delivery_address'] ?? null,
                'delivery_area'         => $data['delivery_area'] ?? null,
                'source'                => $data['source'] ?? 'pos',
                'notes'                 => $data['notes'] ?? null,
                'confirmed_at'          => now(),
            ]);

            // ── 9. Create Order Items ───────────────────────────────────
            foreach ($itemLines as $line) {
                $modifiers = $line['modifiers'] ?? [];
                $orderItem = OrderItem::create(array_merge(
                    ['order_id' => $order->id],
                    array_diff_key($line, ['modifiers' => null])
                ));

                foreach ($modifiers as $mod) {
                    OrderItemModifier::create(array_merge(['order_item_id' => $orderItem->id], $mod));
                }
            }

            // ── 10. Save tax breakdown ──────────────────────────────────
            foreach ($taxBreakdown as $tax) {
                OrderTaxDetail::create(array_merge(['order_id' => $order->id], $tax));
            }

            // ── 11. Create Kitchen Ticket ───────────────────────────────
            $this->createKitchenTicket($order);

            // ── 12. Deduct inventory ────────────────────────────────────
            $this->inventoryService->deductForOrder($order);

            // ── 13. Record coupon usage ─────────────────────────────────
            if ($coupon && $couponDiscount > 0) {
                $coupon->increment('used_count');
                CouponUsage::create([
                    'coupon_id'       => $coupon->id,
                    'order_id'        => $order->id,
                    'customer_id'     => $data['customer_id'] ?? null,
                    'discount_amount' => $couponDiscount,
                    'used_at'         => now(),
                ]);
            }

            // ── 14. Loyalty points redemption ───────────────────────────
            if ($loyaltyDiscount > 0 && ! empty($data['customer_id'])) {
                $this->loyaltyService->redeemPoints(
                    $data['customer_id'],
                    $data['loyalty_redeem_points'],
                    $order->id
                );
                $order->update(['loyalty_points_used' => $loyaltyDiscount]);
            }

            return $order->fresh(['items.modifiers', 'payments', 'taxDetails']);
        });
    }

    /**
     * Process payment(s) for an order. Supports partial / split payments.
     */
    public function processPayment(Order $order, array $payments, int $userId): Order
    {
        return DB::transaction(function () use ($order, $payments, $userId) {
            $totalPaid = 0;

            foreach ($payments as $payment) {
                $order->payments()->create([
                    'branch_id'        => $order->branch_id,
                    'payment_method'   => $payment['method'],
                    'amount'           => $payment['amount'],
                    'reference_number' => $payment['reference'] ?? null,
                    'status'           => 'completed',
                    'paid_at'          => now(),
                    'received_by'      => $userId,
                ]);
                $totalPaid += $payment['amount'];
            }

            $changeDue = max(0, $totalPaid - $order->balance_due);
            $newPaid   = $order->paid_amount + $totalPaid - $changeDue;

            $paymentStatus = $newPaid >= $order->total_amount ? 'paid' : 'partial';

            $order->update([
                'paid_amount'    => $newPaid,
                'change_amount'  => $changeDue,
                'payment_status' => $paymentStatus,
                'status'         => $paymentStatus === 'paid' ? 'completed' : $order->status,
                'completed_at'   => $paymentStatus === 'paid' ? now() : null,
            ]);

            if ($paymentStatus === 'paid') {
                // Earn loyalty points
                if ($order->customer_id) {
                    $pointsEarned = $this->loyaltyService->earnPoints($order->customer_id, $order->total_amount, $order->id);
                    $order->update(['loyalty_points_earned' => $pointsEarned]);
                    $order->customer->recordVisit($order->total_amount);
                }

                // Post accounting journal entry
                $this->accountingService->recordSale($order);

                // Close table session if dine-in
                if ($order->table_session_id) {
                    TableSession::find($order->table_session_id)?->close();
                }
            }

            return $order->fresh();
        });
    }

    /**
     * Split an order into N sub-orders.
     */
    public function splitOrder(Order $order, array $splits, int $userId): array
    {
        return DB::transaction(function () use ($order, $splits, $userId) {
            $newOrders = [];

            foreach ($splits as $split) {
                $splitData = [
                    'branch_id'       => $order->branch_id,
                    'company_id'      => $order->company_id,
                    'order_type'      => $order->order_type,
                    'items'           => $split['items'],
                    'source'          => 'pos',
                    'parent_order_id' => $order->id,
                ];

                $newOrder = $this->placeOrder($splitData, $userId);
                $newOrder->update(['is_split' => true, 'parent_order_id' => $order->id]);
                $newOrders[] = $newOrder;
            }

            $order->update(['is_split' => true]);

            return $newOrders;
        });
    }

    // ── Private Helpers ──────────────────────────────────────────────────

    private function buildItemLines(array $items, int $branchId): array
    {
        $lines = [];
        foreach ($items as $item) {
            $menuItem = MenuItem::with(['taxGroup.rates', 'variants'])->findOrFail($item['menu_item_id']);
            $price    = $menuItem->getPriceForVariant($item['variant_id'] ?? null);
            $qty      = $item['quantity'];

            // Build modifier lines
            $modLines      = [];
            $modifiersTotal = 0;
            foreach ($item['modifiers'] ?? [] as $mod) {
                $modifier = \App\Models\Modifier::find($mod['modifier_id']);
                if (! $modifier) continue;
                $modQty        = $mod['quantity'] ?? 1;
                $modLines[]    = [
                    'modifier_group_id' => $modifier->modifier_group_id,
                    'modifier_id'       => $modifier->id,
                    'group_name'        => $modifier->group->name,
                    'modifier_name'     => $modifier->name,
                    'price'             => $modifier->price,
                    'quantity'          => $modQty,
                ];
                $modifiersTotal += $modifier->price * $modQty;
            }

            $linePrice = ($price + $modifiersTotal) * $qty;

            // Per-item tax
            $taxData  = $menuItem->taxGroup ? $menuItem->taxGroup->calculateTax($linePrice) : ['breakdown' => [], 'total_tax' => 0];
            $taxAmount = $taxData['total_tax'];

            $lines[] = [
                'menu_item_id' => $menuItem->id,
                'variant_id'   => $item['variant_id'] ?? null,
                'item_name'    => $menuItem->name,
                'variant_name' => isset($item['variant_id']) ? $menuItem->variants->find($item['variant_id'])?->name : null,
                'unit_price'   => $price + $modifiersTotal,
                'cost_price'   => $menuItem->cost_price,
                'quantity'     => $qty,
                'discount_amount' => 0,
                'tax_amount'   => $taxAmount,
                'subtotal'     => $linePrice,
                'total'        => $linePrice + $taxAmount,
                'notes'        => $item['notes'] ?? null,
                'modifiers'    => $modLines,
            ];
        }
        return $lines;
    }

    private function applyPromotions(float $subtotal, int $branchId, string $orderType): array
    {
        $promotion = Promotion::active()
            ->forBranch($branchId)
            ->whereIn('type', ['percentage_discount', 'fixed_discount', 'happy_hour'])
            ->orderByDesc('priority')
            ->first();

        if (! $promotion || ! $promotion->isValidNow()) {
            return [0, null];
        }

        $discount = $promotion->calculateDiscount($subtotal);
        return [$discount, $promotion->name];
    }

    private function calculateTaxes(array $itemLines, float $taxableBase): array
    {
        $breakdown          = [];
        $totalVat           = 0;
        $totalServiceCharge = 0;

        foreach ($itemLines as $line) {
            $menuItem = MenuItem::with('taxGroup.rates')->find($line['menu_item_id']);
            if (! $menuItem?->taxGroup) continue;

            foreach ($menuItem->taxGroup->rates as $rate) {
                if (! $rate->is_active) continue;
                $taxAmt = round($line['subtotal'] * $rate->rate / 100, 2);

                $breakdown[] = [
                    'tax_rate_id'    => $rate->id,
                    'tax_name'       => $rate->name,
                    'tax_rate'       => $rate->rate,
                    'taxable_amount' => $line['subtotal'],
                    'tax_amount'     => $taxAmt,
                ];

                if ($rate->type === 'service_charge') {
                    $totalServiceCharge += $taxAmt;
                } else {
                    $totalVat += $taxAmt;
                }
            }
        }

        return [$breakdown, round($totalVat, 2), round($totalServiceCharge, 2)];
    }

    private function createKitchenTicket(Order $order): void
    {
        $lastTicket = KitchenTicket::where('branch_id', $order->branch_id)
                                   ->whereDate('created_at', today())
                                   ->count();

        $ticket = KitchenTicket::create([
            'order_id'     => $order->id,
            'branch_id'    => $order->branch_id,
            'ticket_number'=> str_pad($lastTicket + 1, 3, '0', STR_PAD_LEFT),
            'table_label'  => $order->tableSession?->table->table_number ?? strtoupper($order->order_type),
            'order_type'   => $order->order_type,
            'status'       => 'pending',
            'notes'        => $order->notes,
        ]);

        foreach ($order->items as $item) {
            KitchenTicketItem::create([
                'kitchen_ticket_id' => $ticket->id,
                'order_item_id'     => $item->id,
                'item_name'         => $item->item_name,
                'variant_name'      => $item->variant_name,
                'modifiers'         => $item->modifiers->map(fn ($m) => [
                    'name' => $m->modifier_name,
                    'qty'  => $m->quantity,
                ])->values()->all(),
                'quantity'          => $item->quantity,
                'notes'             => $item->notes,
                'status'            => 'pending',
            ]);
        }
    }
}
