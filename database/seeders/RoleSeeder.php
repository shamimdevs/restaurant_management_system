<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * System roles with their permission sets.
     * Admin gets everything. Others get sensible defaults.
     */
    public function run(): void
    {
        $companyId = DB::table('companies')->value('id');
        $now       = now();

        $roles = [
            'admin'    => [
                'name'        => 'Admin',
                'description' => 'Full system access',
                'is_system'   => true,
                'permissions' => '*',           // all permissions
            ],
            'manager'  => [
                'name'        => 'Manager',
                'description' => 'Branch manager — all except system settings',
                'is_system'   => true,
                'permissions' => [
                    'dashboard.view', 'pos.view', 'pos.create', 'pos.edit', 'pos.discount', 'pos.void',
                    'menu.view', 'menu.create', 'menu.edit',
                    'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.adjust', 'inventory.export',
                    'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.approve', 'purchase.receive',
                    'kitchen.view', 'kitchen.update_status',
                    'tables.view', 'tables.create', 'tables.edit', 'tables.manage_session',
                    'customers.view', 'customers.create', 'customers.edit', 'customers.export',
                    'promotions.view', 'promotions.create', 'promotions.edit',
                    'accounting.view', 'accounting.create', 'accounting.export',
                    'expenses.view', 'expenses.create', 'expenses.approve',
                    'employees.view', 'employees.create', 'employees.edit',
                    'attendance.view', 'attendance.create', 'attendance.edit',
                    'payroll.view', 'payroll.create',
                    'reports.view', 'reports.export',
                    'vat.view', 'vat.export',
                ],
            ],
            'cashier'  => [
                'name'        => 'Cashier',
                'description' => 'POS operations only',
                'is_system'   => true,
                'permissions' => [
                    'dashboard.view', 'pos.view', 'pos.create', 'pos.discount',
                    'customers.view', 'customers.create',
                    'tables.view', 'tables.manage_session',
                    'reports.view',
                ],
            ],
            'waiter'   => [
                'name'        => 'Waiter',
                'description' => 'Table service & order taking',
                'is_system'   => true,
                'permissions' => [
                    'dashboard.view', 'pos.view', 'pos.create',
                    'tables.view', 'tables.manage_session',
                    'menu.view',
                    'kitchen.view',
                ],
            ],
            'kitchen'  => [
                'name'        => 'Kitchen',
                'description' => 'Kitchen Display System access only',
                'is_system'   => true,
                'permissions' => [
                    'kitchen.view', 'kitchen.update_status',
                ],
            ],
            'delivery' => [
                'name'        => 'Delivery',
                'description' => 'Delivery rider',
                'is_system'   => true,
                'permissions' => [
                    'dashboard.view',
                ],
            ],
        ];

        $allPermissions = DB::table('permissions')->pluck('id', 'name');

        foreach ($roles as $slug => $data) {
            $roleId = DB::table('roles')->insertGetId([
                'company_id'  => $companyId,
                'name'        => $data['name'],
                'slug'        => $slug,
                'description' => $data['description'],
                'is_system'   => $data['is_system'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $permissionNames = $data['permissions'] === '*'
                ? $allPermissions->keys()->all()
                : $data['permissions'];

            $pivotRows = [];
            foreach ($permissionNames as $permName) {
                if (isset($allPermissions[$permName])) {
                    $pivotRows[] = [
                        'role_id'       => $roleId,
                        'permission_id' => $allPermissions[$permName],
                    ];
                }
            }

            if (! empty($pivotRows)) {
                DB::table('role_permissions')->insert($pivotRows);
            }
        }

        // Assign admin role to the super admin user
        $adminUser = DB::table('users')->first();
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();

        if ($adminUser && $adminRole) {
            DB::table('user_roles')->insert([
                'user_id'    => $adminUser->id,
                'role_id'    => $adminRole->id,
                'branch_id'  => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
