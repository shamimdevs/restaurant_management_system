<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountingController extends Controller
{
    public function __construct(private readonly AccountingService $accountingService) {}

    /**
     * Render the Accounting dashboard with summary data.
     */
    public function index(Request $request): Response
    {
        $user      = $request->user();
        $branchId  = $user->effectiveBranchId();
        $companyId = $user->company_id;
        $month     = now()->format('Y-m');

        $summary = $this->getMonthlySummary($companyId, $branchId, $month);

        return Inertia::render('Accounting/Index', [
            'summary' => $summary,
        ]);
    }

    // ── Expenses ──────────────────────────────────────────────────────────

    public function getExpenses(Request $request): JsonResponse
    {
        $expenses = Expense::with('category', 'createdBy:id,name')
            ->where('branch_id', $request->user()->effectiveBranchId())
            ->when($request->from,     fn ($q) => $q->whereDate('expense_date', '>=', $request->from))
            ->when($request->to,       fn ($q) => $q->whereDate('expense_date', '<=', $request->to))
            ->when($request->category, fn ($q) => $q->where('expense_category_id', $request->category))
            ->latest('expense_date')
            ->paginate(20);

        return response()->json($expenses);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $request->validate([
            'title'                => 'required|string|max:200',
            'amount'               => 'required|numeric|min:0.01',
            'expense_date'         => 'required|date',
            'expense_category_id'  => 'nullable|exists:expense_categories,id',
            'payment_method'       => 'nullable|string|in:cash,card,bkash,nagad,bank',
            'notes'                => 'nullable|string|max:500',
        ]);

        $expense = Expense::create([
            ...$request->only('title', 'amount', 'expense_date', 'expense_category_id', 'payment_method', 'notes'),
            'branch_id'  => $request->user()->effectiveBranchId(),
            'company_id' => $request->user()->company_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['expense' => $expense->load('category'), 'message' => 'Expense recorded.'], 201);
    }

    // ── Expense categories ────────────────────────────────────────────────

    public function getExpenseCategories(Request $request): JsonResponse
    {
        $cats = ExpenseCategory::where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get();

        return response()->json($cats);
    }

    // ── Journal entries ───────────────────────────────────────────────────

    public function getJournalEntries(Request $request): JsonResponse
    {
        $entries = JournalEntry::with('lines.account')
            ->where('company_id', $request->user()->company_id)
            ->when($request->from, fn ($q) => $q->whereDate('entry_date', '>=', $request->from))
            ->when($request->to,   fn ($q) => $q->whereDate('entry_date', '<=', $request->to))
            ->latest('entry_date')
            ->paginate(25);

        return response()->json($entries);
    }

    // ── Chart of Accounts ─────────────────────────────────────────────────

    public function getAccounts(Request $request): JsonResponse
    {
        $accounts = Account::with('group')
            ->where('company_id', $request->user()->company_id)
            ->orderBy('code')
            ->get();

        return response()->json($accounts);
    }

    // ── Trial Balance ─────────────────────────────────────────────────────

    public function trialBalance(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date']);

        $rows = DB::table('journal_entry_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->join('account_groups as ag', 'ag.id', '=', 'a.account_group_id')
            ->where('je.company_id', $request->user()->company_id)
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$request->from, $request->to])
            ->selectRaw('a.id, a.code, a.name, ag.name as group_name, ag.account_type,
                SUM(jl.debit) as total_debit, SUM(jl.credit) as total_credit,
                SUM(jl.debit) - SUM(jl.credit) as balance')
            ->groupBy('a.id', 'a.code', 'a.name', 'ag.name', 'ag.account_type')
            ->orderBy('a.code')
            ->get();

        return response()->json([
            'rows'          => $rows,
            'total_debit'   => $rows->sum('total_debit'),
            'total_credit'  => $rows->sum('total_credit'),
        ]);
    }

    // ── Profit & Loss ─────────────────────────────────────────────────────

    public function profitLoss(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date']);

        $data = $this->accountingService->getProfitLoss(
            $request->user()->company_id,
            $request->from,
            $request->to
        );

        return response()->json($data);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function getMonthlySummary(int $companyId, ?int $branchId, string $month): array
    {
        $from = "$month-01";
        $to   = now()->format('Y-m-t');

        $totalIncome = DB::table('orders')
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"])
            ->sum('total_amount');

        $totalExpense = DB::table('expenses')
            ->where('branch_id', $branchId)
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        $byCategory = DB::table('expenses as e')
            ->leftJoin('expense_categories as ec', 'ec.id', '=', 'e.expense_category_id')
            ->where('e.branch_id', $branchId)
            ->whereBetween('e.expense_date', [$from, $to])
            ->selectRaw('COALESCE(ec.name, "Uncategorized") as category, SUM(e.amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'month'          => $month,
            'total_income'   => round((float)$totalIncome, 2),
            'total_expense'  => round((float)$totalExpense, 2),
            'net_profit'     => round((float)$totalIncome - (float)$totalExpense, 2),
            'expense_by_cat' => $byCategory,
        ];
    }
}
