<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyBranchSeeder extends Seeder
{
    public function run(): void
    {
        // ── Company ────────────────────────────────────────────────────
        $companyId = DB::table('companies')->insertGetId([
            'name'                 => 'Spice Garden Restaurant',
            'slug'                 => 'spice-garden',
            'address'              => 'House 12, Road 5, Dhanmondi',
            'city'                 => 'Dhaka',
            'phone'                => '+8801700000000',
            'email'                => 'info@spicegarden.com.bd',
            'vat_registration_no'  => 'BIN-000000000-0001',
            'trade_license_no'     => 'DNCC-2024-000001',
            'currency'             => 'BDT',
            'currency_symbol'      => '৳',
            'timezone'             => 'Asia/Dhaka',
            'is_active'            => true,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // ── Branch 1 – Dhanmondi ───────────────────────────────────────
        $branch1Id = DB::table('branches')->insertGetId([
            'company_id'   => $companyId,
            'name'         => 'Dhanmondi Branch',
            'code'         => 'DHK-001',
            'address'      => 'House 12, Road 5, Dhanmondi',
            'city'         => 'Dhaka',
            'phone'        => '+8801711000001',
            'opening_time' => '10:00:00',
            'closing_time' => '23:00:00',
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // ── Branch 2 – Gulshan ─────────────────────────────────────────
        $branch2Id = DB::table('branches')->insertGetId([
            'company_id'   => $companyId,
            'name'         => 'Gulshan Branch',
            'code'         => 'DHK-002',
            'address'      => 'Level 3, Gulshan Avenue, Gulshan-2',
            'city'         => 'Dhaka',
            'phone'        => '+8801711000002',
            'opening_time' => '11:00:00',
            'closing_time' => '23:30:00',
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // ── Super Admin User ───────────────────────────────────────────
        DB::table('users')->insert([
            'company_id'  => $companyId,
            'branch_id'   => null,
            'name'        => 'Super Admin',
            'email'       => 'admin@gmail.com',
            'phone'       => '+8801700000001',
            'password'    => Hash::make('Password@123'),
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
