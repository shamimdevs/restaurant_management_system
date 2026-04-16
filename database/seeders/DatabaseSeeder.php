<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            CompanyBranchSeeder::class,   // creates demo company, branch, admin user
            RoleSeeder::class,            // seeds system roles with permissions
            TaxSeeder::class,             // Bangladesh VAT rates
            AccountChartSeeder::class,    // Chart of Accounts (COA)
            UnitSeeder::class,            // kg, L, pc, dozen …
            DemoDataSeeder::class,        // menu, tables, customers, orders, employees
        ]);
    }
}
