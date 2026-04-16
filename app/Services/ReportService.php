<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderTaxDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Daily sales summary for a branch.
     */
    public function getDailySales(int $branchId, string $date): array
    {
        $orders = Order::where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->with(['payments', 'items'])
            ->get();

        $byType = $orders->groupBy('order_type')->map(fn ($g) => [
            'count'   => $g->count(),
            'revenue' => $g->sum('total_amount'),
        ]);

        $byPayment = OrderPayment::whereIn('order_id', $orders->pluck('id'))
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount) as total')
            ->pluck('total', 'payment_method');

        return [
            'date'           => $date,
            'total_orders'   => $orders->count(),
            'total_revenue'  => $orders->sum('total_amount'),
            'total_vat'      => $orders->sum('vat_amount'),
            'total_discount' => $orders->sum('discount_amount') + $orders->sum('coupon_discount'),
            'avg_order_value'=> $orders->count() ? round($orders->sum('total_amount') / $orders->count(), 2) : 0,
            'by_type'        => $byType,
            'by_payment'     => $byPayment,
        ];
    }

    /**
     * Sales summary for a date range.
     */
    public function getSalesReport(int $branchId, string $from, string $to): array
    {
        $orders = Order::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->dateRange($from, $to)
            ->get();

        $daily = $orders->groupBy(fn ($o) => $o->created_at->toDateString())
            ->map(fn ($group) => [
                'date'    => $group->first()->created_at->toDateString(),
                'orders'  => $group->count(),
                'revenue' => round($group->sum('total_amount'), 2),
                'vat'     => round($group->sum('vat_amount'), 2),
            ])->values();

        return [
            'from'           => $from,
            'to'             => $to,
            'total_orders'   => $orders->count(),
            'total_revenue'  => round($orders->sum('total_amount'), 2),
            'total_vat'      => round($orders->sum('vat_amount'), 2),
            'total_discount' => round($orders->sum('discount_amount') + $orders->sum('coupon_discount'), 2),
            'daily'          => $daily,
        ];
    }

    /**
     * Top-selling menu items.
     */
    public function getTopItems(int $branchId, string $from, string $to, int $limit = 10): Collection
    {
        return OrderItem::select(
                'menu_item_id',
                'item_name',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_id) as order_count')
            )
            ->whereHas('order', fn ($q) =>
                $q->where('branch_id', $branchId)
                  ->where('status', 'completed')
                  ->dateRange($from, $to)
            )
            ->groupBy('menu_item_id', 'item_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Expense summary for a date range.
     */
    public function getExpenseReport(int $branchId, string $from, string $to): array
    {
        $expenses = Expense::where('branch_id', $branchId)
            ->where('status', 'approved')
            ->dateRange($from, $to)
            ->with('category')
            ->get();

        $byCategory = $expenses->groupBy('expense_category_id')->map(fn ($g) => [
            'category' => $g->first()->category->name,
            'total'    => round($g->sum('amount'), 2),
            'count'    => $g->count(),
        ])->values();

        return [
            'total_expenses' => round($expenses->sum('amount'), 2),
            'total_vat'      => round($expenses->sum('vat_amount'), 2),
            'by_category'    => $byCategory,
        ];
    }

    /**
     * Profit & Loss statement.
     */
    public function getProfitLoss(int $branchId, string $from, string $to): array
    {
        $revenue  = Order::where('branch_id', $branchId)->where('status', 'completed')->dateRange($from, $to)->sum('subtotal');
        $discounts = Order::where('branch_id', $branchId)->where('status', 'completed')->dateRange($from, $to)
            ->selectRaw('SUM(discount_amount + coupon_discount) as total')->value('total') ?? 0;
        $netRevenue   = $revenue - $discounts;
        $expenses     = Expense::where('branch_id', $branchId)->where('status', 'approved')->dateRange($from, $to)->sum('amount');

        return [
            'from'              => $from,
            'to'                => $to,
            'gross_revenue'     => round($revenue, 2),
            'total_discounts'   => round($discounts, 2),
            'net_revenue'       => round($netRevenue, 2),
            'total_expenses'    => round($expenses, 2),
            'gross_profit'      => round($netRevenue - $expenses, 2),
        ];
    }

    /**
     * VAT report for a period — used for NBR filing (Mushak 9.1).
     */
    public function getVatReport(int $branchId, string $from, string $to): array
    {
        $taxDetails = OrderTaxDetail::whereHas('order', fn ($q) =>
                $q->where('branch_id', $branchId)
                  ->where('status', 'completed')
                  ->dateRange($from, $to)
            )
            ->groupBy('tax_name', 'tax_rate')
            ->selectRaw('tax_name, tax_rate, SUM(taxable_amount) as taxable_amount, SUM(tax_amount) as tax_amount')
            ->get();

        $totalVatCollected = $taxDetails->where('tax_name', '!=', 'Service Charge')->sum('tax_amount');
        $totalSC           = $taxDetails->where('tax_name', 'Service Charge')->sum('tax_amount');
        $totalSales        = Order::where('branch_id', $branchId)->where('status', 'completed')->dateRange($from, $to)->sum('total_amount');

        return [
            'period_start'             => $from,
            'period_end'               => $to,
            'total_sales'              => round($totalSales, 2),
            'total_vat_collected'      => round($totalVatCollected, 2),
            'service_charge_collected' => round($totalSC, 2),
            'breakdown'                => $taxDetails,
        ];
    }

    /**
     * Branch-wise performance comparison.
     */
    public function getBranchPerformance(int $companyId, string $from, string $to): Collection
    {
        return Order::select(
                'branch_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('AVG(total_amount) as avg_order'),
                DB::raw('SUM(vat_amount) as total_vat')
            )
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->dateRange($from, $to)
            ->groupBy('branch_id')
            ->with('branch:id,name,code')
            ->get();
    }
}
