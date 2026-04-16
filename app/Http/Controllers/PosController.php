<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Requests\Order\ProcessPaymentRequest;
use App\Models\Category;
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
        $user   = $request->user();
        $branch = $user->branch;

        $categories = Category::active()
            ->forBranch($branch->id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color', 'image']);

        $tables = RestaurantTable::where('branch_id', $branch->id)
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
     * Load menu items for a category (AJAX).
     */
    public function getMenuItems(Request $request): JsonResponse
    {
        $request->validate(['category_id' => 'required|integer', 'branch_id' => 'required|integer']);

        $items = MenuItem::with(['variants', 'modifierGroups.modifiers', 'taxGroup.rates'])
            ->available()
            ->forBranch($request->branch_id)
            ->where('category_id', $request->category_id)
            ->orderBy('sort_order')
            ->get();

        return response()->json($items);
    }

    /**
     * Search menu items (for POS search bar).
     */
    public function searchItems(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1', 'branch_id' => 'required|integer']);

        $items = MenuItem::with(['variants', 'modifierGroups.modifiers'])
            ->available()
            ->forBranch($request->branch_id)
            ->where(fn ($q) =>
                $q->where('name', 'like', "%{$request->q}%")
                  ->orWhere('sku', $request->q)
                  ->orWhere('barcode', $request->q)
            )
            ->limit(20)
            ->get();

        return response()->json($items);
    }

    /**
     * Place a new order.
     */
    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->placeOrder(
            array_merge($request->validated(), [
                'company_id' => $request->user()->company_id,
                'branch_id'  => $request->user()->branch_id ?? $request->branch_id,
            ]),
            $request->user()->id
        );

        return response()->json(['order' => $order, 'message' => 'Order placed successfully.'], 201);
    }

    /**
     * Process payment for an order.
     */
    public function processPayment(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('branch', $order);

        $order = $this->orderService->processPayment(
            $order,
            $request->validated('payments'),
            $request->user()->id
        );

        return response()->json(['order' => $order, 'message' => 'Payment recorded.']);
    }

    /**
     * Split an order.
     */
    public function splitOrder(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'splits'           => 'required|array|min:2',
            'splits.*.items'   => 'required|array',
        ]);

        $newOrders = $this->orderService->splitOrder($order, $request->splits, $request->user()->id);

        return response()->json(['orders' => $newOrders, 'message' => 'Order split successfully.']);
    }

    /**
     * Apply coupon to get discount preview.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string', 'subtotal' => 'required|numeric|min:0']);

        $coupon = \App\Models\Coupon::where('code', $request->code)
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
     * Today's orders list for this branch.
     */
    public function todayOrders(Request $request): JsonResponse
    {
        $orders = Order::where('branch_id', $request->user()->branch_id)
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
}
