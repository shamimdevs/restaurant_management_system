<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockAlert;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(Request $request): Response
    {
        $user      = $request->user();
        $branchId  = $user->branch_id;
        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Super-admin with no branch: default to first branch of the company
        if (is_null($branchId)) {
            $branchId = Branch::where('company_id', $user->company_id)
                ->where('is_active', true)
                ->value('id');
        }

        // ── KPI Stats ────────────────────────────────────────────────────
        $todayStats     = $this->getDayStats($branchId, $today);
        $yesterdayStats = $this->getDayStats($branchId, $yesterday);

        $stats = [
            'today_revenue'   => $todayStats['revenue'],
            'today_orders'    => $todayStats['orders'],
            'today_customers' => $todayStats['customers'],
            'avg_order_value' => $todayStats['orders'] > 0
                ? round($todayStats['revenue'] / $todayStats['orders'], 2) : 0,
            'revenue_change'  => $this->percentChange($yesterdayStats['revenue'], $todayStats['revenue']),
            'orders_change'   => $this->percentChange($yesterdayStats['orders'], $todayStats['orders']),
        ];

        // ── Hourly Revenue ────────────────────────────────────────────────
        $hourlyRevenue = $this->getHourlyRevenue($branchId, $today);

        // ── Top Items Today ───────────────────────────────────────────────
        $topItems = $branchId
            ? OrderItem::select('item_name', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(total) as revenue'))
                ->whereHas('order', fn ($q) => $q
                    ->where('branch_id', $branchId)
                    ->where('status', 'completed')
                    ->whereDate('created_at', $today)
                )
                ->groupBy('item_name')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get(['item_name as name', 'quantity', 'revenue'])
            : collect();

        // ── Recent Orders ─────────────────────────────────────────────────
        $recentOrders = $branchId
            ? Order::with('tableSession.table:id,table_number')
                ->where('branch_id', $branchId)
                ->whereDate('created_at', $today)
                ->latest()
                ->limit(10)
                ->get(['id', 'order_number', 'status', 'total_amount as total', 'order_type', 'table_session_id', 'created_at'])
                ->map(fn ($o) => [
                    'id'           => $o->id,
                    'order_number' => $o->order_number,
                    'status'       => $o->status,
                    'total'        => $o->total,
                    'order_type'   => $o->order_type,
                    'created_at'   => $o->created_at,
                    'table'        => $o->tableSession?->table
                        ? ['table_number' => $o->tableSession->table->table_number]
                        : null,
                ])
            : collect();

        // ── Stock Alerts ──────────────────────────────────────────────────
        $alerts = $branchId
            ? StockAlert::with('ingredient:id,name')
                ->where('branch_id', $branchId)
                ->where('is_resolved', false)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
            : collect();

        return Inertia::render('Dashboard/Index', compact(
            'stats', 'hourlyRevenue', 'topItems', 'recentOrders', 'alerts'
        ));
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function getDayStats(?int $branchId, string $date): array
    {
        if (is_null($branchId)) {
            return ['orders' => 0, 'revenue' => 0.0, 'customers' => 0];
        }

        $orders = Order::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereDate('created_at', $date)
            ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as revenue, COUNT(DISTINCT customer_id) as customers')
            ->first();

        return [
            'orders'    => (int)($orders->cnt ?? 0),
            'revenue'   => (float)($orders->revenue ?? 0),
            'customers' => (int)($orders->customers ?? 0),
        ];
    }

    private function getHourlyRevenue(?int $branchId, string $date): array
    {
        if (is_null($branchId)) {
            return collect(range(6, 23))->map(fn ($h) => ['hour' => sprintf('%02d:00', $h), 'revenue' => 0.0])->values()->all();
        }

        $rows = Order::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereDate('created_at', $date)
            ->selectRaw('HOUR(created_at) as h, SUM(total_amount) as revenue')
            ->groupBy('h')
            ->orderBy('h')
            ->pluck('revenue', 'h');

        return collect(range(6, 23))->map(fn ($h) => [
            'hour'    => sprintf('%02d:00', $h),
            'revenue' => (float)($rows[$h] ?? 0),
        ])->values()->all();
    }

    private function percentChange(float $old, float $new): ?float
    {
        if ($old == 0) return null;
        return round((($new - $old) / $old) * 100, 1);
    }
}
