<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Orders/History');
    }

    public function list(Request $request): JsonResponse
    {
        $user     = $request->user();
        $branchId = $user->effectiveBranchId();

        $orders = Order::with([
                'customer:id,name,phone',
                'user:id,name',
                'tableSession.table:id,table_number,name',
                'payments:id,order_id,payment_method,amount,status',
            ])
            ->where('branch_id', $branchId)
            ->when($request->from,           fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,             fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->status,         fn ($q) => $q->where('status', $request->status))
            ->when($request->order_type,     fn ($q) => $q->where('order_type', $request->order_type))
            ->when($request->payment_status, fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->search, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('order_number', 'like', "%{$request->search}%")
                   ->orWhereHas('customer', fn ($q3) =>
                       $q3->where('name', 'like', "%{$request->search}%")
                          ->orWhere('phone', 'like', "%{$request->search}%")
                   )
            ))
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'branch:id,name,address,city,phone,email',
            'company:id,name,address,city,phone,email,vat_registration_no,trade_license_no,currency_symbol',
            'customer:id,name,phone,email',
            'user:id,name',
            'tableSession.table:id,table_number,name',
            'items.modifiers',
            'payments',
            'taxDetails',
            'coupon:id,code',
        ]);

        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:confirmed,preparing,ready,served,completed,cancelled',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($request->status === 'cancelled') {
            if ($order->isPaid()) {
                return response()->json(['message' => 'Cannot cancel a paid order.'], 422);
            }
            $order->cancel($request->reason ?? 'Cancelled by staff');
        } else {
            $order->update([
                'status'       => $request->status,
                'completed_at' => $request->status === 'completed' ? now() : $order->completed_at,
            ]);
        }

        return response()->json(['order' => $order->fresh(), 'message' => 'Status updated.']);
    }

    public function todayStats(Request $request): JsonResponse
    {
        $branchId = $request->user()->effectiveBranchId();

        $stats = Order::where('branch_id', $branchId)
            ->today()
            ->selectRaw("
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status NOT IN ('completed','cancelled') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue,
                SUM(CASE WHEN payment_status = 'paid' THEN vat_amount ELSE 0 END) as vat,
                SUM(CASE WHEN payment_status = 'paid' THEN discount_amount + coupon_discount ELSE 0 END) as discounts,
                AVG(CASE WHEN payment_status = 'paid' THEN total_amount ELSE NULL END) as avg_order
            ")
            ->first();

        return response()->json($stats);
    }
}
