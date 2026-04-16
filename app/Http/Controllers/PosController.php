<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\PlaceOrderRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * Render the POS screen (Inertia).
     */
    public function index(Request $request): Response
    {
        $user     = $request->user();
        $branchId = $user->effectiveBranchId();
        $branch   = $user->branch ?? Branch::find($branchId);

        $categories = Category::active()
            ->forBranch($branchId)
            ->withCount('menuItems')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color', 'image']);

        $tables = RestaurantTable::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('table_number')
            ->get(['id', 'table_number', 'name', 'capacity', 'status']);

        return Inertia::render('Pos/Index', [
            'categories' => $categories,
            'tables'     => $tables,
            'branch'     => $branch,
        ]);
    }

    /**
     * Unified item endpoint — browse by category OR search by keyword.
     * GET /api/pos/items?category_id=1&search=chicken&per_page=50
     */
    public function getMenuItems(Request $request): JsonResponse
    {
        $user     = $request->user();
        $branchId = $user->effectiveBranchId();

        $query = MenuItem::with(['variants', 'modifierGroups.modifiers'])
            ->available()
            ->forBranch($branchId);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($q2) =>
                $q2->where('name', 'like', "%{$q}%")
                   ->orWhere('sku', $q)
                   ->orWhere('barcode', $q)
            );
        } elseif ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->orderBy('sort_order')->orderBy('name')->limit(80)->get();

        return response()->json($items);
    }

    /**
     * Place a new order AND process the payment in one shot (POS flow).
     */
    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $user     = $request->user();
        $branchId = $request->branch_id ?? $user->effectiveBranchId();

        $orderData = array_merge($request->validated(), [
            'company_id' => $user->company_id,
            'branch_id'  => $branchId,
            // map frontend key → service key
            'loyalty_redeem_points' => $request->input('loyalty_points_redeemed') ?? $request->input('loyalty_redeem_points'),
        ]);

        $order = $this->orderService->placeOrder($orderData, $user->id);

        // Process inline payments (POS sends them in the same request)
        if (! empty($orderData['payments'])) {
            $validPayments = collect($orderData['payments'])
                ->filter(fn ($p) => ($p['amount'] ?? 0) > 0)
                ->values()
                ->all();

            if (! empty($validPayments)) {
                $order = $this->orderService->processPayment($order, $validPayments, $user->id);
            }
        }

        return response()->json([
            'order'   => $order->load(['items', 'payments']),
            'message' => 'Order placed successfully.',
        ], 201);
    }

    /**
     * Process payment for an existing order (separate endpoint).
     */
    public function processPayment(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'payments'           => 'required|array|min:1',
            'payments.*.method'  => 'required|in:cash,card,bkash,nagad,rocket,upay,loyalty_points',
            'payments.*.amount'  => 'required|numeric|min:0.01',
            'payments.*.reference' => 'nullable|string|max:100',
        ]);

        $order = $this->orderService->processPayment(
            $order,
            $request->payments,
            $request->user()->id
        );

        return response()->json(['order' => $order, 'message' => 'Payment recorded.']);
    }

    /**
     * Apply coupon — returns discount amount for frontend preview.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code'     => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)
            ->where('company_id', $request->user()->company_id)
            ->first();

        if (! $coupon) {
            return response()->json(['message' => 'Coupon not found.'], 404);
        }

        if (! $coupon->isValid($request->user()->id)) {
            return response()->json(['message' => 'Coupon is expired or not applicable.'], 422);
        }

        $discount = $coupon->calculateDiscount($request->subtotal);

        return response()->json([
            'coupon'   => $coupon->only('id', 'code', 'description', 'discount_type', 'discount_value'),
            'discount' => $discount,
        ]);
    }

    /**
     * Today's orders list.
     */
    public function todayOrders(Request $request): JsonResponse
    {
        $branchId = $request->user()->effectiveBranchId();

        $orders = Order::where('branch_id', $branchId)
            ->today()
            ->with(['items', 'payments', 'customer:id,name,phone', 'tableSession.table:id,table_number'])
            ->latest()
            ->paginate(25);

        return response()->json($orders);
    }

    /**
     * Void / cancel an order.
     */
    public function voidOrder(Request $request, Order $order): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:255']);

        if ($order->isPaid()) {
            return response()->json(['message' => 'Cannot void a paid order.'], 422);
        }

        $order->cancel($request->reason);

        return response()->json(['message' => 'Order voided.']);
    }

    /**
     * Split an order.
     */
    public function splitOrder(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'splits'         => 'required|array|min:2',
            'splits.*.items' => 'required|array',
        ]);

        $newOrders = $this->orderService->splitOrder($order, $request->splits, $request->user()->id);

        return response()->json(['orders' => $newOrders, 'message' => 'Order split successfully.']);
    }
}
