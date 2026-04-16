<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * All system permissions.
     * Format: module.action
     */
    public function run(): void
    {
        $now = now();

        $modules = [
            'dashboard'   => ['view'],
            'pos'         => ['view', 'create', 'edit', 'discount', 'void', 'refund'],
            'menu'        => ['view', 'create', 'edit', 'delete'],
            'inventory'   => ['view', 'create', 'edit', 'delete', 'adjust', 'export'],
            'purchase'    => ['view', 'create', 'edit', 'delete', 'approve', 'receive'],
            'kitchen'     => ['view', 'update_status'],
            'tables'      => ['view', 'create', 'edit', 'delete', 'manage_session'],
            'customers'   => ['view', 'create', 'edit', 'delete', 'export'],
            'promotions'  => ['view', 'create', 'edit', 'delete'],
            'accounting'  => ['view', 'create', 'edit', 'delete', 'post', 'export'],
            'expenses'    => ['view', 'create', 'edit', 'delete', 'approve'],
            'employees'   => ['view', 'create', 'edit', 'delete'],
            'attendance'  => ['view', 'create', 'edit'],
            'payroll'     => ['view', 'create', 'process', 'export'],
            'reports'     => ['view', 'export'],
            'vat'         => ['view', 'file', 'export'],
            'settings'    => ['view', 'edit'],
            'branches'    => ['view', 'create', 'edit', 'delete'],
            'users'       => ['view', 'create', 'edit', 'delete'],
            'roles'       => ['view', 'create', 'edit', 'delete'],
        ];

        $permissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissions[] = [
                    'module'       => $module,
                    'action'       => $action,
                    'name'         => "{$module}.{$action}",
                    'display_name' => ucfirst($module) . ' - ' . ucfirst($action),
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        DB::table('permissions')->upsert($permissions, ['name'], ['display_name', 'updated_at']);
    }
}
