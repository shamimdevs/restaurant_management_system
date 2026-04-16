<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountChartSeeder extends Seeder
{
    /**
     * Standard Chart of Accounts for a Bangladeshi restaurant.
     * Follows double-entry bookkeeping principles.
     */
    public function run(): void
    {
        $companyId = DB::table('companies')->value('id');
        $now       = now();

        /*
         * Structure:
         *  1xxx – Assets
         *  2xxx – Liabilities
         *  3xxx – Equity
         *  4xxx – Income / Revenue
         *  5xxx – Cost of Goods Sold (COGS)
         *  6xxx – Operating Expenses
         */

        // ── Account Groups ─────────────────────────────────────────────
        $groups = [
            ['name' => 'Assets',                    'type' => 'asset',     'code' => '1000', 'normal_balance' => 'debit',  'parent' => null],
            ['name' => '  Current Assets',          'type' => 'asset',     'code' => '1100', 'normal_balance' => 'debit',  'parent' => '1000'],
            ['name' => '  Fixed Assets',            'type' => 'asset',     'code' => '1200', 'normal_balance' => 'debit',  'parent' => '1000'],
            ['name' => 'Liabilities',               'type' => 'liability', 'code' => '2000', 'normal_balance' => 'credit', 'parent' => null],
            ['name' => '  Current Liabilities',     'type' => 'liability', 'code' => '2100', 'normal_balance' => 'credit', 'parent' => '2000'],
            ['name' => 'Equity',                    'type' => 'equity',    'code' => '3000', 'normal_balance' => 'credit', 'parent' => null],
            ['name' => 'Income',                    'type' => 'income',    'code' => '4000', 'normal_balance' => 'credit', 'parent' => null],
            ['name' => 'Cost of Goods Sold',        'type' => 'expense',   'code' => '5000', 'normal_balance' => 'debit',  'parent' => null],
            ['name' => 'Operating Expenses',        'type' => 'expense',   'code' => '6000', 'normal_balance' => 'debit',  'parent' => null],
            ['name' => '  Payroll Expenses',        'type' => 'expense',   'code' => '6100', 'normal_balance' => 'debit',  'parent' => '6000'],
            ['name' => '  Utility Expenses',        'type' => 'expense',   'code' => '6200', 'normal_balance' => 'debit',  'parent' => '6000'],
            ['name' => '  Administrative Expenses', 'type' => 'expense',   'code' => '6300', 'normal_balance' => 'debit',  'parent' => '6000'],
        ];

        $groupIds = [];
        foreach ($groups as $g) {
            $parentId = $g['parent'] ? ($groupIds[$g['parent']] ?? null) : null;
            $id = DB::table('account_groups')->insertGetId([
                'company_id'     => $companyId,
                'parent_id'      => $parentId,
                'name'           => trim($g['name']),
                'code'           => $g['code'],
                'type'           => $g['type'],
                'normal_balance' => $g['normal_balance'],
                'is_system'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            $groupIds[$g['code']] = $id;
        }

        // ── Accounts ───────────────────────────────────────────────────
        $accounts = [
            // Current Assets
            ['group' => '1100', 'code' => '1101', 'name' => 'Cash in Hand',               'type' => 'asset'],
            ['group' => '1100', 'code' => '1102', 'name' => 'Cash at Bank (DBBL)',         'type' => 'asset'],
            ['group' => '1100', 'code' => '1103', 'name' => 'bKash Agent Account',         'type' => 'asset'],
            ['group' => '1100', 'code' => '1104', 'name' => 'Nagad Agent Account',         'type' => 'asset'],
            ['group' => '1100', 'code' => '1105', 'name' => 'Accounts Receivable',         'type' => 'asset'],
            ['group' => '1100', 'code' => '1106', 'name' => 'Inventory / Raw Materials',   'type' => 'asset'],
            ['group' => '1100', 'code' => '1107', 'name' => 'Prepaid Expenses',            'type' => 'asset'],
            ['group' => '1100', 'code' => '1108', 'name' => 'VAT Input Credit (Paid)',     'type' => 'asset'],

            // Fixed Assets
            ['group' => '1200', 'code' => '1201', 'name' => 'Furniture & Fixtures',        'type' => 'asset'],
            ['group' => '1200', 'code' => '1202', 'name' => 'Kitchen Equipment',           'type' => 'asset'],
            ['group' => '1200', 'code' => '1203', 'name' => 'POS Equipment',               'type' => 'asset'],
            ['group' => '1200', 'code' => '1204', 'name' => 'Accumulated Depreciation',    'type' => 'asset'],

            // Current Liabilities
            ['group' => '2100', 'code' => '2101', 'name' => 'Accounts Payable',            'type' => 'liability'],
            ['group' => '2100', 'code' => '2102', 'name' => 'VAT Payable (Output)',        'type' => 'liability'],
            ['group' => '2100', 'code' => '2103', 'name' => 'Service Charge Payable',      'type' => 'liability'],
            ['group' => '2100', 'code' => '2104', 'name' => 'Salary Payable',              'type' => 'liability'],
            ['group' => '2100', 'code' => '2105', 'name' => 'TDS Payable',                 'type' => 'liability'],
            ['group' => '2100', 'code' => '2106', 'name' => 'Customer Loyalty Points',     'type' => 'liability'],

            // Equity
            ['group' => '3000', 'code' => '3001', 'name' => 'Owner Equity',                'type' => 'equity'],
            ['group' => '3000', 'code' => '3002', 'name' => 'Retained Earnings',           'type' => 'equity'],

            // Income
            ['group' => '4000', 'code' => '4001', 'name' => 'Food Sales Revenue',          'type' => 'income'],
            ['group' => '4000', 'code' => '4002', 'name' => 'Beverage Sales Revenue',      'type' => 'income'],
            ['group' => '4000', 'code' => '4003', 'name' => 'Delivery Revenue',            'type' => 'income'],
            ['group' => '4000', 'code' => '4004', 'name' => 'Catering Revenue',            'type' => 'income'],
            ['group' => '4000', 'code' => '4005', 'name' => 'Discount Given',              'type' => 'income'],  // contra

            // COGS
            ['group' => '5000', 'code' => '5001', 'name' => 'Cost of Food Sold',           'type' => 'expense'],
            ['group' => '5000', 'code' => '5002', 'name' => 'Cost of Beverages Sold',      'type' => 'expense'],
            ['group' => '5000', 'code' => '5003', 'name' => 'Packaging & Containers',      'type' => 'expense'],

            // Payroll
            ['group' => '6100', 'code' => '6101', 'name' => 'Basic Salaries',              'type' => 'expense'],
            ['group' => '6100', 'code' => '6102', 'name' => 'Overtime Pay',                'type' => 'expense'],
            ['group' => '6100', 'code' => '6103', 'name' => 'Bonus & Incentives',          'type' => 'expense'],

            // Utilities
            ['group' => '6200', 'code' => '6201', 'name' => 'Electricity Bill',            'type' => 'expense'],
            ['group' => '6200', 'code' => '6202', 'name' => 'Gas Bill',                    'type' => 'expense'],
            ['group' => '6200', 'code' => '6203', 'name' => 'Water Bill',                  'type' => 'expense'],
            ['group' => '6200', 'code' => '6204', 'name' => 'Internet & Phone',            'type' => 'expense'],

            // Admin
            ['group' => '6300', 'code' => '6301', 'name' => 'Rent Expense',                'type' => 'expense'],
            ['group' => '6300', 'code' => '6302', 'name' => 'Marketing & Advertising',     'type' => 'expense'],
            ['group' => '6300', 'code' => '6303', 'name' => 'Cleaning & Maintenance',      'type' => 'expense'],
            ['group' => '6300', 'code' => '6304', 'name' => 'Office & Stationery',         'type' => 'expense'],
            ['group' => '6300', 'code' => '6305', 'name' => 'Depreciation Expense',        'type' => 'expense'],
            ['group' => '6300', 'code' => '6306', 'name' => 'Miscellaneous Expense',       'type' => 'expense'],
        ];

        foreach ($accounts as $a) {
            DB::table('accounts')->insert([
                'company_id'       => $companyId,
                'account_group_id' => $groupIds[$a['group']],
                'name'             => $a['name'],
                'code'             => $a['code'],
                'type'             => $a['type'],
                'is_system'        => true,
                'is_active'        => true,
                'opening_balance'  => 0,
                'current_balance'  => 0,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }
}
