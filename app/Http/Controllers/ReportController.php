<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function dashboard(Request $request): Response
    {
        $branchId = $request->user()->effectiveBranchId();
        $today    = now()->toDateString();

        return Inertia::render('Reports/Dashboard', [
            'daily_sales' => $this->reportService->getDailySales($branchId, $today),
            'top_items'   => $this->reportService->getTopItems($branchId, now()->startOfMonth()->toDateString(), $today, 5),
        ]);
    }

    public function sales(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);

        $data = $this->reportService->getSalesReport(
            $request->user()->effectiveBranchId(),
            $request->from,
            $request->to
        );

        return response()->json($data);
    }

    public function topItems(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date', 'limit' => 'nullable|integer']);

        $items = $this->reportService->getTopItems(
            $request->user()->effectiveBranchId(),
            $request->from,
            $request->to,
            $request->limit ?? 10
        );

        return response()->json($items);
    }

    public function expenses(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date']);

        $data = $this->reportService->getExpenseReport($request->user()->effectiveBranchId(), $request->from, $request->to);
        return response()->json($data);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date']);

        $data = $this->reportService->getProfitLoss($request->user()->effectiveBranchId(), $request->from, $request->to);
        return response()->json($data);
    }

    public function vatReport(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date']);

        $data = $this->reportService->getVatReport($request->user()->effectiveBranchId(), $request->from, $request->to);
        return response()->json($data);
    }

    public function branchPerformance(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date']);

        $data = $this->reportService->getBranchPerformance($request->user()->company_id, $request->from, $request->to);
        return response()->json($data);
    }
}
