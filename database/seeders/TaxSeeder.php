<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSeeder extends Seeder
{
    /**
     * Bangladesh Tax / VAT configuration.
     *
     * NBR (National Board of Revenue) rates applicable to restaurants:
     *  - Standard VAT     : 15%   (most goods & services)
     *  - Restaurant VAT   :  5%   (truncated rate under SRO 195-AIN/2019)
     *  - Service Charge   : 10%   (hotels, high-end restaurants)
     *  - VAT Exempt       :  0%   (basic food items, medicine)
     *  - SD (Supp. Duty)  : varies (cigarettes, alcohol - N/A for halal restaurants)
     */
    public function run(): void
    {
        $companyId = DB::table('companies')->value('id');
        $now       = now();

        $groups = [
            [
                'name'        => 'Restaurant VAT (5%)',
                'description' => 'Standard truncated VAT for restaurant dine-in & delivery (SRO 195)',
                'is_default'  => true,
                'rates'       => [
                    ['name' => 'VAT', 'type' => 'vat', 'rate' => 5.00, 'is_inclusive' => false],
                ],
            ],
            [
                'name'        => 'Restaurant VAT (7.5%)',
                'description' => 'Mid-tier restaurant VAT rate',
                'is_default'  => false,
                'rates'       => [
                    ['name' => 'VAT', 'type' => 'vat', 'rate' => 7.50, 'is_inclusive' => false],
                ],
            ],
            [
                'name'        => 'Standard VAT (15%)',
                'description' => 'Full standard VAT rate (NBR)',
                'is_default'  => false,
                'rates'       => [
                    ['name' => 'VAT', 'type' => 'vat', 'rate' => 15.00, 'is_inclusive' => false],
                ],
            ],
            [
                'name'        => 'VAT + Service Charge',
                'description' => 'Restaurant VAT 5% + Service Charge 10% (high-end)',
                'is_default'  => false,
                'rates'       => [
                    ['name' => 'VAT',            'type' => 'vat',            'rate' => 5.00,  'is_inclusive' => false],
                    ['name' => 'Service Charge', 'type' => 'service_charge', 'rate' => 10.00, 'is_inclusive' => false],
                ],
            ],
            [
                'name'        => 'VAT Exempt',
                'description' => 'Zero-rated / exempt items',
                'is_default'  => false,
                'rates'       => [
                    ['name' => 'Exempt', 'type' => 'vat', 'rate' => 0.00, 'is_inclusive' => false],
                ],
            ],
        ];

        foreach ($groups as $group) {
            $groupId = DB::table('tax_groups')->insertGetId([
                'company_id'  => $companyId,
                'name'        => $group['name'],
                'description' => $group['description'],
                'is_default'  => $group['is_default'],
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            foreach ($group['rates'] as $i => $rate) {
                DB::table('tax_rates')->insert([
                    'tax_group_id'  => $groupId,
                    'name'          => $rate['name'],
                    'type'          => $rate['type'],
                    'rate'          => $rate['rate'],
                    'is_inclusive'  => $rate['is_inclusive'],
                    'sort_order'    => $i,
                    'is_active'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }
}
