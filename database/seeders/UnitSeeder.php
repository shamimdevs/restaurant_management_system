<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('companies')->value('id');
        $now       = now();

        $units = [
            // Weight
            ['name' => 'Kilogram',    'abbreviation' => 'kg',    'type' => 'weight', 'is_base_unit' => true],
            ['name' => 'Gram',        'abbreviation' => 'g',     'type' => 'weight', 'is_base_unit' => false],
            ['name' => 'Milligram',   'abbreviation' => 'mg',    'type' => 'weight', 'is_base_unit' => false],

            // Volume
            ['name' => 'Liter',       'abbreviation' => 'L',     'type' => 'volume', 'is_base_unit' => true],
            ['name' => 'Milliliter',  'abbreviation' => 'ml',    'type' => 'volume', 'is_base_unit' => false],

            // Count
            ['name' => 'Piece',       'abbreviation' => 'pc',    'type' => 'count',  'is_base_unit' => true],
            ['name' => 'Dozen',       'abbreviation' => 'dz',    'type' => 'count',  'is_base_unit' => false],
            ['name' => 'Packet',      'abbreviation' => 'pkt',   'type' => 'count',  'is_base_unit' => false],
            ['name' => 'Box',         'abbreviation' => 'box',   'type' => 'count',  'is_base_unit' => false],
            ['name' => 'Bottle',      'abbreviation' => 'btl',   'type' => 'count',  'is_base_unit' => false],
            ['name' => 'Can',         'abbreviation' => 'can',   'type' => 'count',  'is_base_unit' => false],
            ['name' => 'Bag',         'abbreviation' => 'bag',   'type' => 'count',  'is_base_unit' => false],
            ['name' => 'Tray',        'abbreviation' => 'tray',  'type' => 'count',  'is_base_unit' => false],

            // Restaurant-specific
            ['name' => 'Portion',     'abbreviation' => 'ptn',   'type' => 'count',  'is_base_unit' => false],
            ['name' => 'Cup',         'abbreviation' => 'cup',   'type' => 'volume', 'is_base_unit' => false],
            ['name' => 'Tablespoon',  'abbreviation' => 'tbsp',  'type' => 'volume', 'is_base_unit' => false],
            ['name' => 'Teaspoon',    'abbreviation' => 'tsp',   'type' => 'volume', 'is_base_unit' => false],
        ];

        foreach ($units as $unit) {
            DB::table('units')->insert([
                'company_id'   => $companyId,
                'name'         => $unit['name'],
                'abbreviation' => $unit['abbreviation'],
                'type'         => $unit['type'],
                'is_base_unit' => $unit['is_base_unit'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}
