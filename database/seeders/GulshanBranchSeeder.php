<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GulshanBranchSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('companies')->where('slug', 'spice-garden')->value('id');
        $branch2Id = DB::table('branches')->where('code', 'DHK-002')->value('id');

        if (! $companyId || ! $branch2Id) {
            $this->command->error('Company or Gulshan branch not found. Run CompanyBranchSeeder first.');
            return;
        }

        // Existing emails in DB - skip them
        $existingEmails = DB::table('users')->pluck('email')->toArray();

        $roles = DB::table('roles')->whereIn('slug', ['manager', 'cashier', 'waiter', 'kitchen'])
            ->pluck('id', 'slug');

        // ── Create Gulshan department ───────────────────────────────────
        $deptId = DB::table('departments')->insertGetId([
            'branch_id'  => $branch2Id,
            'name'       => 'Operations',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $designations = DB::table('designations')->pluck('id', 'name');

        // ── Staff users ─────────────────────────────────────────────────
        $staff = [
            [
                'name'  => 'Arif Manager',
                'email' => 'arif@spicegarden.com.bd',
                'phone' => '+8801722000001',
                'role'  => 'manager',
                'desig' => 'Manager',
                'emp_id'=> 'EMP-GL-001',
                'salary'=> 32000,
            ],
            [
                'name'  => 'Nipa Cashier',
                'email' => 'nipa@spicegarden.com.bd',
                'phone' => '+8801722000002',
                'role'  => 'cashier',
                'desig' => 'Cashier',
                'emp_id'=> 'EMP-GL-002',
                'salary'=> 18000,
            ],
            [
                'name'  => 'Reza Waiter',
                'email' => 'reza@spicegarden.com.bd',
                'phone' => '+8801722000003',
                'role'  => 'waiter',
                'desig' => 'Waiter',
                'emp_id'=> 'EMP-GL-003',
                'salary'=> 14000,
            ],
            [
                'name'  => 'Kamal Chef',
                'email' => 'kamal@spicegarden.com.bd',
                'phone' => '+8801722000004',
                'role'  => 'kitchen',
                'desig' => 'Chef',
                'emp_id'=> 'EMP-GL-004',
                'salary'=> 22000,
            ],
        ];

        foreach ($staff as $s) {
            if (in_array($s['email'], $existingEmails)) {
                $this->command->warn("Skipping {$s['email']} — already exists.");
                continue;
            }

            $userId = DB::table('users')->insertGetId([
                'company_id' => $companyId,
                'branch_id'  => $branch2Id,
                'name'       => $s['name'],
                'email'      => $s['email'],
                'phone'      => $s['phone'],
                'password'   => Hash::make('Password@123'),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (isset($roles[$s['role']])) {
                DB::table('user_roles')->insert([
                    'user_id'    => $userId,
                    'role_id'    => $roles[$s['role']],
                    'branch_id'  => $branch2Id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('employees')->insert([
                'user_id'        => $userId,
                'branch_id'      => $branch2Id,
                'department_id'  => $deptId,
                'designation_id' => $designations[$s['desig']] ?? null,
                'employee_id'    => $s['emp_id'],
                'name'           => $s['name'],
                'phone'          => $s['phone'],
                'email'          => $s['email'],
                'joining_date'   => now()->subMonths(rand(2, 8))->toDateString(),
                'salary_type'    => 'monthly',
                'basic_salary'   => $s['salary'],
                'status'         => 'active',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // ── Floor plan & tables for Gulshan (only if not already created) ──
        $hasFloor = DB::table('floor_plans')->where('branch_id', $branch2Id)->exists();
        if ($hasFloor) {
            $this->command->info('Gulshan floor plan already exists, skipping tables.');
            $this->command->info('Done.');
            return;
        }

        $floorId = DB::table('floor_plans')->insertGetId([
            'branch_id'  => $branch2Id,
            'name'       => 'Main Hall',
            'sort_order' => 1,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 1; $i <= 8; $i++) {
            DB::table('restaurant_tables')->insert([
                'branch_id'    => $branch2Id,
                'floor_plan_id'=> $floorId,
                'table_number' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'name'         => 'Table ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'capacity'     => ($i <= 4) ? 4 : 6,
                'shape'        => 'rectangle',
                'status'       => 'available',
                'qr_code'      => 'GL-T' . str_pad($i, 2, '0', STR_PAD_LEFT) . '-' . uniqid(),
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $this->command->info('Gulshan branch seeded: 4 staff users + 8 tables.');
    }
}
