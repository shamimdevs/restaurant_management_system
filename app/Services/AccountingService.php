<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Post a double-entry journal for a completed sale.
     *
     * Debit:  Cash/MFS accounts   (per payment method)
     * Credit: Sales Revenue        (food / beverage)
     * Credit: VAT Payable          (if applicable)
     * Credit: Service Charge Payable
     */
    public function recordSale(Order $order): JournalEntry
    {
        return DB::transaction(function () use ($order) {
            $company = $order->company;

            $entry = JournalEntry::create([
                'company_id'     => $company->id,
                'branch_id'      => $order->branch_id,
                'entry_number'   => JournalEntry::generateEntryNumber($company->id),
                'entry_date'     => today(),
                'description'    => "Sale - Order #{$order->order_number}",
                'reference_type' => Order::class,
                'reference_id'   => $order->id,
                'status'         => 'posted',
                'created_by'     => $order->user_id ?? 1,
                'posted_by'      => $order->user_id ?? 1,
                'posted_at'      => now(),
            ]);

            $lines = [];

            // DEBIT lines — one per payment method
            foreach ($order->payments()->where('status', 'completed')->get() as $payment) {
                $account = $this->getPaymentAccount($payment->payment_method, $company->id);
                if ($account) {
                    $lines[] = ['account_id' => $account->id, 'description' => "Payment ({$payment->payment_method})", 'debit' => $payment->amount, 'credit' => 0];
                }
            }

            // CREDIT — Sales Revenue
            $salesAccount = Account::where('company_id', $company->id)->where('code', '4001')->first();
            if ($salesAccount) {
                $revenue = $order->subtotal - $order->discount_amount - $order->coupon_discount;
                $lines[] = ['account_id' => $salesAccount->id, 'description' => 'Sales Revenue', 'debit' => 0, 'credit' => $revenue];
            }

            // CREDIT — VAT Payable
            if ($order->vat_amount > 0) {
                $vatAccount = Account::where('company_id', $company->id)->where('code', '2102')->first();
                if ($vatAccount) {
                    $lines[] = ['account_id' => $vatAccount->id, 'description' => 'VAT Payable', 'debit' => 0, 'credit' => $order->vat_amount];
                }
            }

            // CREDIT — Service Charge Payable
            if ($order->service_charge > 0) {
                $scAccount = Account::where('company_id', $company->id)->where('code', '2103')->first();
                if ($scAccount) {
                    $lines[] = ['account_id' => $scAccount->id, 'description' => 'Service Charge', 'debit' => 0, 'credit' => $order->service_charge];
                }
            }

            foreach ($lines as $line) {
                JournalEntryLine::create(array_merge(['journal_entry_id' => $entry->id], $line));
            }

            $entry->update([
                'total_debit'  => collect($lines)->sum('debit'),
                'total_credit' => collect($lines)->sum('credit'),
            ]);

            // Update account balances
            collect($lines)->pluck('account_id')->unique()
                ->each(fn ($id) => Account::find($id)?->recalculateBalance());

            return $entry;
        });
    }

    /**
     * Record an expense as a journal entry.
     *
     * Debit:  Expense Account
     * Credit: Cash / Bank
     */
    public function recordExpense(Expense $expense): JournalEntry
    {
        return DB::transaction(function () use ($expense) {
            $company = $expense->branch->company;

            $entry = JournalEntry::create([
                'company_id'     => $company->id,
                'branch_id'      => $expense->branch_id,
                'entry_number'   => JournalEntry::generateEntryNumber($company->id),
                'entry_date'     => $expense->expense_date,
                'description'    => "Expense: {$expense->description}",
                'reference_type' => Expense::class,
                'reference_id'   => $expense->id,
                'status'         => 'posted',
                'created_by'     => $expense->created_by,
                'posted_by'      => $expense->created_by,
                'posted_at'      => now(),
            ]);

            $expenseAccount = $expense->category->account
                ?? Account::where('company_id', $company->id)->where('code', '6306')->first();

            $cashAccount = $this->getPaymentAccount($expense->payment_method, $company->id);

            $lines = [
                ['account_id' => $expenseAccount?->id, 'description' => $expense->description, 'debit' => $expense->amount, 'credit' => 0],
                ['account_id' => $cashAccount?->id,    'description' => "Paid via {$expense->payment_method}", 'debit' => 0, 'credit' => $expense->amount],
            ];

            foreach (array_filter($lines, fn ($l) => $l['account_id']) as $line) {
                JournalEntryLine::create(array_merge(['journal_entry_id' => $entry->id], $line));
            }

            $entry->update(['total_debit' => $expense->amount, 'total_credit' => $expense->amount]);
            $expense->update(['journal_entry_id' => $entry->id]);

            return $entry;
        });
    }

    /**
     * Generate Trial Balance for a company.
     */
    public function getTrialBalance(int $companyId): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('group')
            ->get();

        $totalDebit = $totalCredit = 0;
        $rows = $accounts->map(function ($account) use (&$totalDebit, &$totalCredit) {
            $debitTotal  = $account->journalLines()->sum('debit');
            $creditTotal = $account->journalLines()->sum('credit');
            $balance     = in_array($account->type, ['asset', 'expense'])
                ? $account->opening_balance + $debitTotal - $creditTotal
                : $account->opening_balance + $creditTotal - $debitTotal;

            if ($balance >= 0) {
                $totalDebit += $balance;
                return ['account' => $account->name, 'code' => $account->code, 'debit' => $balance, 'credit' => 0];
            } else {
                $totalCredit += abs($balance);
                return ['account' => $account->name, 'code' => $account->code, 'debit' => 0, 'credit' => abs($balance)];
            }
        });

        return ['rows' => $rows, 'total_debit' => $totalDebit, 'total_credit' => $totalCredit];
    }

    /**
     * Profit & Loss summary for a date range.
     */
    public function getProfitLoss(int $companyId, string $from, string $to): array
    {
        $income = DB::table('orders')
            ->whereIn('branch_id', fn ($q) => $q->select('id')->from('branches')->where('company_id', $companyId))
            ->where('status', 'completed')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('total_amount');

        $expenses = DB::table('expenses')
            ->whereIn('branch_id', fn ($q) => $q->select('id')->from('branches')->where('company_id', $companyId))
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        return [
            'from'       => $from,
            'to'         => $to,
            'income'     => round((float)$income, 2),
            'expenses'   => round((float)$expenses, 2),
            'net_profit' => round((float)$income - (float)$expenses, 2),
        ];
    }

    // ── Private ──────────────────────────────────────────────────────────

    private function getPaymentAccount(string $method, int $companyId): ?Account
    {
        $codeMap = [
            'cash'          => '1101',
            'card'          => '1102',
            'bkash'         => '1103',
            'nagad'         => '1104',
            'rocket'        => '1103',
            'bank_transfer' => '1102',
        ];

        $code = $codeMap[$method] ?? '1101';
        return Account::where('company_id', $companyId)->where('code', $code)->first();
    }
}
