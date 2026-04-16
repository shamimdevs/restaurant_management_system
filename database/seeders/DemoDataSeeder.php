<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company  = DB::table('companies')->first();
        $branch1  = DB::table('branches')->where('code', 'DHK-001')->first();
        $branch2  = DB::table('branches')->where('code', 'DHK-002')->first();
        $taxGroup = DB::table('tax_groups')->where('is_default', true)->first();
        $kgUnit   = DB::table('units')->where('abbreviation', 'kg')->value('id');
        $lUnit    = DB::table('units')->where('abbreviation', 'L')->value('id');
        $pcUnit   = DB::table('units')->where('name', 'Piece')->value('id');
        $now      = now();

        // ── Floor Plans ───────────────────────────────────────────────────
        $fp1Id = DB::table('floor_plans')->insertGetId([
            'branch_id'  => $branch1->id,
            'name'       => 'Main Hall',
            'sort_order' => 1,
            'is_active'  => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $fp2Id = DB::table('floor_plans')->insertGetId([
            'branch_id'  => $branch1->id,
            'name'       => 'Rooftop',
            'sort_order' => 2,
            'is_active'  => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // ── Tables ────────────────────────────────────────────────────────
        $tableData = [
            ['T01', 'Table 01', 2, $fp1Id, 'square'],
            ['T02', 'Table 02', 2, $fp1Id, 'square'],
            ['T03', 'Table 03', 4, $fp1Id, 'rectangle'],
            ['T04', 'Table 04', 4, $fp1Id, 'rectangle'],
            ['T05', 'Table 05', 6, $fp1Id, 'rectangle'],
            ['T06', 'Table 06', 6, $fp1Id, 'rectangle'],
            ['T07', 'Table 07', 8, $fp1Id, 'rectangle'],
            ['T08', 'Table 08', 4, $fp1Id, 'round'],
            ['R01', 'Rooftop 01', 4, $fp2Id, 'round'],
            ['R02', 'Rooftop 02', 6, $fp2Id, 'rectangle'],
        ];
        $tableIds = [];
        foreach ($tableData as [$num, $name, $cap, $fp, $shape]) {
            $tableIds[$num] = DB::table('restaurant_tables')->insertGetId([
                'branch_id'    => $branch1->id,
                'floor_plan_id'=> $fp,
                'table_number' => $num,
                'name'         => $name,
                'capacity'     => $cap,
                'shape'        => $shape,
                'status'       => 'available',
                'qr_code'      => Str::uuid(),
                'is_active'    => true,
                'created_at'   => $now, 'updated_at' => $now,
            ]);
        }

        // ── Menu Categories ───────────────────────────────────────────────
        $catData = [
            ['Appetizers',    'appetizers',    '#ef4444', 1],
            ['Main Course',   'main-course',   '#f59e0b', 2],
            ['Rice & Biryani','rice-biryani',  '#8b5cf6', 3],
            ['Breads',        'breads',        '#f97316', 4],
            ['Soups',         'soups',         '#06b6d4', 5],
            ['Beverages',     'beverages',     '#10b981', 6],
            ['Desserts',      'desserts',      '#ec4899', 7],
        ];
        $catIds = [];
        foreach ($catData as [$name, $slug, $color, $sort]) {
            $catIds[$slug] = DB::table('categories')->insertGetId([
                'company_id' => $company->id,
                'name'       => $name,
                'slug'       => $slug,
                'color'      => $color,
                'sort_order' => $sort,
                'is_active'  => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ── Menu Items ────────────────────────────────────────────────────
        $menuItems = [
            // Appetizers
            ['Vegetable Spring Roll',      'vegetable-spring-roll',      $catIds['appetizers'],   120,  60,  6,  'food'],
            ['Chicken Seekh Kebab',        'chicken-seekh-kebab',        $catIds['appetizers'],   220, 100,  8,  'food'],
            ['Paneer Tikka',               'paneer-tikka',               $catIds['appetizers'],   280, 130, 10,  'food'],
            ['Mixed Platter',              'mixed-platter',              $catIds['appetizers'],   450, 200, 15,  'food'],
            // Main Course
            ['Chicken Butter Masala',      'chicken-butter-masala',      $catIds['main-course'],  380, 170, 20,  'food'],
            ['Mutton Rogan Josh',          'mutton-rogan-josh',          $catIds['main-course'],  520, 250, 25,  'food'],
            ['Fish Bhuna',                 'fish-bhuna',                 $catIds['main-course'],  420, 190, 18,  'food'],
            ['Dal Makhani',                'dal-makhani',                $catIds['main-course'],  220,  80, 12,  'food'],
            ['Palak Paneer',               'palak-paneer',               $catIds['main-course'],  300, 120, 15,  'food'],
            ['Chicken Rezala',             'chicken-rezala',             $catIds['main-course'],  360, 160, 20,  'food'],
            // Rice & Biryani
            ['Kacchi Biryani (Half)',       'kacchi-biryani-half',        $catIds['rice-biryani'], 380, 160, 25,  'food'],
            ['Kacchi Biryani (Full)',       'kacchi-biryani-full',        $catIds['rice-biryani'], 680, 290, 25,  'food'],
            ['Chicken Fried Rice',         'chicken-fried-rice',         $catIds['rice-biryani'], 260, 100, 15,  'food'],
            ['Vegetable Pulao',            'vegetable-pulao',            $catIds['rice-biryani'], 200,  80, 12,  'food'],
            // Breads
            ['Naan',                       'naan',                       $catIds['breads'],        40,  15,  3,  'food'],
            ['Paratha',                    'paratha',                    $catIds['breads'],        50,  20,  3,  'food'],
            ['Tandoori Roti',              'tandoori-roti',              $catIds['breads'],        35,  12,  3,  'food'],
            // Soups
            ['Tomato Soup',                'tomato-soup',                $catIds['soups'],        120,  45,  8,  'food'],
            ['Chicken Clear Soup',         'chicken-clear-soup',         $catIds['soups'],        150,  60, 10,  'food'],
            // Beverages
            ['Mango Lassi',                'mango-lassi',                $catIds['beverages'],    120,  40,  5,  'beverage'],
            ['Fresh Lime Soda',            'fresh-lime-soda',            $catIds['beverages'],     80,  25,  3,  'beverage'],
            ['Masala Chai',                'masala-chai',                $catIds['beverages'],     60,  20,  3,  'beverage'],
            ['Cold Coffee',                'cold-coffee',                $catIds['beverages'],    150,  55,  5,  'beverage'],
            // Desserts
            ['Gulab Jamun',                'gulab-jamun',                $catIds['desserts'],     120,  45,  5,  'food'],
            ['Ras Malai',                  'ras-malai',                  $catIds['desserts'],     150,  60,  7,  'food'],
            ['Ice Cream (2 Scoop)',         'ice-cream-2-scoop',          $catIds['desserts'],     130,  50,  5,  'food'],
        ];

        $menuItemIds = [];
        foreach ($menuItems as [$name, $slug, $catId, $price, $cost, $prepTime, $type]) {
            $menuItemIds[$slug] = DB::table('menu_items')->insertGetId([
                'company_id'       => $company->id,
                'branch_id'        => null,  // available to all branches
                'category_id'      => $catId,
                'tax_group_id'     => $taxGroup->id,
                'name'             => $name,
                'slug'             => $slug,
                'base_price'       => $price,
                'cost_price'       => $cost,
                'type'             => $type,
                'preparation_time' => $prepTime,
                'unit'             => 'portion',
                'is_available'     => true,
                'is_featured'      => in_array($slug, ['kacchi-biryani-full', 'chicken-butter-masala', 'mutton-rogan-josh']),
                'track_inventory'  => false,
                'sort_order'       => 0,
                'created_at'       => $now, 'updated_at' => $now,
            ]);
        }

        // ── Ingredients (Inventory) ───────────────────────────────────────
        $ingredients = [
            ['Chicken',       'CHK-001', $kgUnit,  480, 25.0,  5.0, 50.0],
            ['Mutton',        'MTN-001', $kgUnit,  850, 12.0,  3.0, 30.0],
            ['Fish (Rui)',     'FSH-001', $kgUnit,  320,  8.0,  2.0, 20.0],
            ['Basmati Rice',  'RCE-001', $kgUnit,   95, 40.0, 10.0, 80.0],
            ['Cooking Oil',   'OIL-001', $lUnit,   180, 18.0,  5.0, 40.0],
            ['Onion',         'ONI-001', $kgUnit,   45, 30.0,  5.0, 60.0],
            ['Tomato',        'TOM-001', $kgUnit,   60, 15.0,  3.0, 30.0],
            ['Garlic',        'GRL-001', $kgUnit,  280,  5.0,  1.0, 15.0],
            ['Ginger',        'GNG-001', $kgUnit,  220,  4.0,  1.0, 12.0],
            ['Milk',          'MLK-001', $lUnit,    72, 20.0,  5.0, 40.0],
            ['Paneer',        'PNR-001', $kgUnit,  580,  2.0,  1.0, 10.0],  // low stock
            ['Flour (Maida)', 'FLR-001', $kgUnit,   55, 25.0,  5.0, 50.0],
            ['Butter',        'BTR-001', $kgUnit,  780,  3.0,  1.0,  8.0],  // low stock
        ];

        $ingredientIds = [];
        foreach ($ingredients as [$name, $sku, $unitId, $cost, $current, $min, $max]) {
            $ingredientIds[$sku] = DB::table('ingredients')->insertGetId([
                'company_id'      => $company->id,
                'branch_id'       => $branch1->id,
                'unit_id'         => $unitId,
                'name'            => $name,
                'sku'             => $sku,
                'cost_per_unit'   => $cost,
                'current_stock'   => $current,
                'min_stock_level' => $min,
                'max_stock_level' => $max,
                'reorder_point'   => $min * 1.5,
                'is_active'       => true,
                'track_stock'     => true,
                'created_at'      => $now, 'updated_at' => $now,
            ]);
        }

        // Stock alerts for low-stock items
        DB::table('stock_alerts')->insert([
            [
                'branch_id'          => $branch1->id,
                'ingredient_id'      => $ingredientIds['PNR-001'],
                'alert_type'         => 'low_stock',
                'current_quantity'   => 2.0,
                'threshold_quantity' => 1.0,
                'is_resolved'        => false,
                'created_at'         => $now, 'updated_at' => $now,
            ],
            [
                'branch_id'          => $branch1->id,
                'ingredient_id'      => $ingredientIds['BTR-001'],
                'alert_type'         => 'low_stock',
                'current_quantity'   => 3.0,
                'threshold_quantity' => 1.0,
                'is_resolved'        => false,
                'created_at'         => $now, 'updated_at' => $now,
            ],
        ]);

        // ── Customers ─────────────────────────────────────────────────────
        $customers = [
            ['Rahim Uddin',     '+8801711100001', 'rahim@example.com',   'male',   1200, 5800,  12],
            ['Fatema Begum',    '+8801722200002', 'fatema@example.com',  'female',  850, 3200,   8],
            ['Karim Hossain',   '+8801733300003', null,                  'male',    550, 2100,   5],
            ['Nasrin Akter',    '+8801744400004', 'nasrin@example.com',  'female',  320, 1500,   4],
            ['Sohel Rana',      '+8801755500005', null,                  'male',    180,  780,   2],
            ['Mitu Khatun',     '+8801766600006', 'mitu@example.com',    'female',   90,  420,   1],
            ['Arif Khan',       '+8801777700007', null,                  'male',   2500, 9800,  24],
            ['Sultana Parvin',  '+8801788800008', 'sultana@example.com', 'female',  650, 2900,   6],
            ['Jahangir Alam',   '+8801799900009', null,                  'male',    400, 1800,   3],
            ['Roksana Islam',   '+8801700000010', 'roksana@example.com', 'female',  110,  560,   1],
        ];
        $customerIds = [];
        foreach ($customers as $i => [$name, $phone, $email, $gender, $points, $spent, $visits]) {
            $customerIds[] = DB::table('customers')->insertGetId([
                'company_id'     => $company->id,
                'name'           => $name,
                'phone'          => $phone,
                'email'          => $email,
                'gender'         => $gender,
                'loyalty_points' => $points,
                'total_spent'    => $spent,
                'visit_count'    => $visits,
                'last_visit_at'  => now()->subDays(rand(1, 14)),
                'segment'        => $spent > 5000 ? 'vip' : ($spent > 1500 ? 'regular' : 'new'),
                'is_active'      => true,
                'created_at'     => now()->subDays(rand(30, 180)),
                'updated_at'     => $now,
            ]);
        }

        // ── Branch Staff Users ────────────────────────────────────────────
        $managerRole = DB::table('roles')->where('slug', 'manager')->first();
        $cashierRole = DB::table('roles')->where('slug', 'cashier')->first();
        $waiterRole  = DB::table('roles')->where('slug', 'waiter')->first();
        $kitchenRole = DB::table('roles')->where('slug', 'kitchen')->first();

        $staffUsers = [
            ['Rakib Manager',  'rakib@spicegarden.com.bd',  $managerRole->id],
            ['Sumon Cashier',  'sumon@spicegarden.com.bd',  $cashierRole->id],
            ['Milon Waiter',   'milon@spicegarden.com.bd',  $waiterRole->id],
            ['Jamal Kitchen',  'jamal@spicegarden.com.bd',  $kitchenRole->id],
        ];
        $staffUserIds = [];
        foreach ($staffUsers as [$name, $email, $roleId]) {
            $uid = DB::table('users')->insertGetId([
                'company_id' => $company->id,
                'branch_id'  => $branch1->id,
                'name'       => $name,
                'email'      => $email,
                'phone'      => '+88017' . rand(10000000, 99999999),
                'password'   => Hash::make('Password@123'),
                'is_active'  => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('user_roles')->insert([
                'user_id'    => $uid,
                'role_id'    => $roleId,
                'branch_id'  => $branch1->id,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $staffUserIds[] = $uid;
        }

        // ── Departments & Designations ────────────────────────────────────
        $deptId = DB::table('departments')->insertGetId([
            'branch_id'  => $branch1->id,
            'name'       => 'Operations',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $desigIds = [];
        foreach (['Manager', 'Cashier', 'Waiter', 'Chef'] as $d) {
            $desigIds[$d] = DB::table('designations')->insertGetId([
                'company_id' => $company->id,
                'name'       => $d,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ── Employees ─────────────────────────────────────────────────────
        $empData = [
            [$staffUserIds[0], 'EMP-001', 'Rakib Manager',  'male',   $desigIds['Manager'], 35000, 'monthly'],
            [$staffUserIds[1], 'EMP-002', 'Sumon Cashier',  'male',   $desigIds['Cashier'], 18000, 'monthly'],
            [$staffUserIds[2], 'EMP-003', 'Milon Waiter',   'male',   $desigIds['Waiter'],  15000, 'monthly'],
            [$staffUserIds[3], 'EMP-004', 'Jamal Chef',     'male',   $desigIds['Chef'],    22000, 'monthly'],
        ];
        foreach ($empData as [$uid, $empId, $name, $gender, $desigId, $salary, $salType]) {
            DB::table('employees')->insertGetId([
                'user_id'        => $uid,
                'branch_id'      => $branch1->id,
                'department_id'  => $deptId,
                'designation_id' => $desigId,
                'employee_id'    => $empId,
                'name'           => $name,
                'gender'         => $gender,
                'joining_date'   => now()->subMonths(rand(3, 18))->toDateString(),
                'salary_type'    => $salType,
                'basic_salary'   => $salary,
                'status'         => 'active',
                'created_at'     => $now, 'updated_at' => $now,
            ]);
        }

        // ── Settings ──────────────────────────────────────────────────────
        $settingsData = [
            ['general', 'restaurant_name',     'Spice Garden Restaurant', 'string'],
            ['general', 'restaurant_phone',     '+8801700000000',          'string'],
            ['general', 'restaurant_address',   'House 12, Road 5, Dhanmondi, Dhaka', 'string'],
            ['general', 'currency',             'BDT',                     'string'],
            ['general', 'currency_symbol',      '৳',                       'string'],
            ['receipt', 'receipt_header',       'Spice Garden Restaurant', 'string'],
            ['receipt', 'receipt_footer',       'Thank you for dining with us!', 'string'],
            ['receipt', 'show_vat_on_receipt',  '1',                       'boolean'],
            ['pos',     'default_tax_group_id', (string)$taxGroup->id,     'integer'],
            ['pos',     'allow_discount',       '1',                       'boolean'],
            ['pos',     'max_discount_pct',     '20',                      'integer'],
        ];
        foreach ($settingsData as [$group, $key, $value, $type]) {
            DB::table('settings')->insert([
                'company_id' => $company->id,
                'branch_id'  => null,
                'group'      => $group,
                'key'        => $key,
                'value'      => $value,
                'type'       => $type,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ── Past Orders (last 14 days for chart data) ─────────────────────
        $adminUser   = DB::table('users')->where('branch_id', null)->first();
        $popularItems = [
            ['Kacchi Biryani (Full)',   $menuItemIds['kacchi-biryani-full'],   680, 3],
            ['Kacchi Biryani (Half)',   $menuItemIds['kacchi-biryani-half'],   380, 2],
            ['Chicken Butter Masala',   $menuItemIds['chicken-butter-masala'], 380, 2],
            ['Mutton Rogan Josh',       $menuItemIds['mutton-rogan-josh'],     520, 1],
            ['Mango Lassi',             $menuItemIds['mango-lassi'],           120, 2],
            ['Naan',                    $menuItemIds['naan'],                   40, 4],
            ['Gulab Jamun',             $menuItemIds['gulab-jamun'],           120, 1],
        ];

        $orderCount = 0;
        $ticketCount = 0;

        for ($d = 14; $d >= 0; $d--) {
            $day = now()->subDays($d);
            $ordersPerDay = rand(8, 20);

            for ($o = 0; $o < $ordersPerDay; $o++) {
                $orderCount++;
                $hour = rand(10, 22);
                $min  = rand(0, 59);
                $createdAt = $day->copy()->setHour($hour)->setMinute($min)->setSecond(0);
                $custId = $customerIds[array_rand($customerIds)];
                $tableNum = array_keys($tableIds)[rand(0, count($tableIds) - 1)];
                $tableId  = $tableIds[$tableNum];

                $orderNumber = 'ORD-' . $day->format('Ymd') . '-' . str_pad($orderCount, 4, '0', STR_PAD_LEFT);

                // Pick 1-4 items
                $selectedItems = array_values((array)array_rand($popularItems, rand(1, min(4, count($popularItems)))));
                if (!is_array($selectedItems)) $selectedItems = [$selectedItems];

                $subtotal = 0;
                $orderItemsToInsert = [];
                foreach ($selectedItems as $idx) {
                    [$iName, $iId, $iPrice, $iQty] = $popularItems[$idx];
                    $qty  = rand(1, $iQty);
                    $line = $iPrice * $qty;
                    $subtotal += $line;
                    $orderItemsToInsert[] = [$iName, $iId, $iPrice, $qty, $line];
                }
                $vatAmount  = round($subtotal * 0.05, 2);
                $total      = $subtotal + $vatAmount;

                // Create a closed table session for this order
                $sessionId = DB::table('table_sessions')->insertGetId([
                    'branch_id'           => $branch1->id,
                    'restaurant_table_id' => $tableId,
                    'customer_id'         => $custId,
                    'pax'                 => rand(1, 4),
                    'status'              => 'closed',
                    'started_at'          => $createdAt,
                    'ended_at'            => $createdAt->copy()->addMinutes(rand(30, 90)),
                    'created_at'          => $createdAt,
                    'updated_at'          => $createdAt,
                ]);

                $orderId = DB::table('orders')->insertGetId([
                    'order_number'    => $orderNumber,
                    'branch_id'       => $branch1->id,
                    'company_id'      => $company->id,
                    'customer_id'     => $custId,
                    'user_id'         => $adminUser->id,
                    'table_session_id'=> $sessionId,
                    'order_type'      => 'dine_in',
                    'status'          => 'completed',
                    'payment_status'  => 'paid',
                    'subtotal'        => $subtotal,
                    'vat_amount'      => $vatAmount,
                    'total_amount'    => $total,
                    'paid_amount'     => $total,
                    'change_amount'   => 0,
                    'source'          => 'pos',
                    'completed_at'    => $createdAt->copy()->addMinutes(rand(20, 45)),
                    'created_at'      => $createdAt,
                    'updated_at'      => $createdAt,
                ]);

                foreach ($orderItemsToInsert as [$iName, $iId, $iPrice, $qty, $line]) {
                    DB::table('order_items')->insert([
                        'order_id'    => $orderId,
                        'menu_item_id'=> $iId,
                        'item_name'   => $iName,
                        'unit_price'  => $iPrice,
                        'quantity'    => $qty,
                        'subtotal'    => $iPrice * $qty,
                        'total'       => $iPrice * $qty,
                        'status'      => 'served',
                        'created_at'  => $createdAt,
                        'updated_at'  => $createdAt,
                    ]);
                }

                // Kitchen ticket
                $ticketCount++;
                DB::table('kitchen_tickets')->insert([
                    'order_id'      => $orderId,
                    'branch_id'     => $branch1->id,
                    'ticket_number' => 'KT-' . str_pad($ticketCount, 5, '0', STR_PAD_LEFT),
                    'table_label'   => "Table $tableNum",
                    'order_type'    => 'dine_in',
                    'status'        => 'served',
                    'priority'      => 5,
                    'cooking_started_at' => $createdAt->copy()->addMinutes(5),
                    'ready_at'      => $createdAt->copy()->addMinutes(20),
                    'served_at'     => $createdAt->copy()->addMinutes(25),
                    'bump_count'    => 0,
                    'created_at'    => $createdAt,
                    'updated_at'    => $createdAt,
                ]);
            }
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info("Menu items: " . DB::table('menu_items')->count());
        $this->command->info("Orders: " . DB::table('orders')->count());
        $this->command->info("Tables: " . DB::table('restaurant_tables')->count());
    }
}
