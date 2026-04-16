<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Units of Measure
        // ---------------------------------------------------------------
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);                        // Kilogram, Liter, Piece
            $table->string('abbreviation', 10);                // kg, L, pc
            $table->enum('type', ['weight', 'volume', 'count', 'length', 'other'])->default('count');
            $table->boolean('is_base_unit')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'abbreviation']);
        });

        // ---------------------------------------------------------------
        // Ingredients  (raw materials / stock items)
        // ---------------------------------------------------------------
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete(); // null = shared
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku', 50)->nullable()->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->decimal('cost_per_unit', 10, 4)->default(0);  // BDT per unit
            $table->decimal('current_stock', 12, 4)->default(0);  // always up-to-date
            $table->decimal('min_stock_level', 12, 4)->default(0); // triggers low-stock alert
            $table->decimal('max_stock_level', 12, 4)->nullable();
            $table->decimal('reorder_point', 12, 4)->nullable();   // auto-PO trigger threshold
            $table->string('storage_location')->nullable();        // "Dry Store - Shelf A2"
            $table->boolean('is_active')->default(true);
            $table->boolean('track_stock')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id', 'is_active']);
        });

        // ---------------------------------------------------------------
        // Recipes  (how to make a menu item)
        // branch_id = null means recipe is company-wide
        // ---------------------------------------------------------------
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('menu_item_variants')->nullOnDelete();
            $table->decimal('yield_quantity', 8, 4)->default(1);  // how many servings this recipe makes
            $table->foreignId('yield_unit_id')->constrained('units')->cascadeOnDelete();
            $table->text('instructions')->nullable();
            $table->integer('prep_time_minutes')->default(0);
            $table->integer('cook_time_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('menu_item_id');
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);                // per 1 yield
            $table->decimal('waste_percentage', 5, 2)->default(0); // 5% = only 95% usable
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['recipe_id', 'ingredient_id']);
        });

        // ---------------------------------------------------------------
        // Stock Movements  (every in/out is logged here)
        // ---------------------------------------------------------------
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'purchase',     // PO received
                'usage',        // consumed by order
                'adjustment',   // manual correction
                'transfer_in',  // received from another branch
                'transfer_out', // sent to another branch
                'waste',        // spoilage / expired
                'return',       // returned to supplier
                'opening',      // initial stock entry
            ]);
            $table->decimal('quantity', 12, 4);                // positive = in, negative = out
            $table->decimal('unit_cost', 10, 4)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->decimal('stock_before', 12, 4)->default(0);
            $table->decimal('stock_after', 12, 4)->default(0);
            $table->nullableMorphs('reference');               // order, purchase_order, adjustment
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->date('movement_date');
            $table->timestamps();

            $table->index(['branch_id', 'ingredient_id', 'type']);
            $table->index(['branch_id', 'movement_date']);
        });

        // ---------------------------------------------------------------
        // Inventory Adjustments  (supervisor approval workflow)
        // ---------------------------------------------------------------
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('reference_no', 30)->unique();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        Schema::create('inventory_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('system_quantity', 12, 4);         // what system thinks we have
            $table->decimal('actual_quantity', 12, 4);         // physical count
            $table->decimal('difference', 12, 4);              // actual - system
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ---------------------------------------------------------------
        // Stock Alerts  (triggered when ingredient falls below min level)
        // ---------------------------------------------------------------
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->enum('alert_type', ['low_stock', 'out_of_stock', 'near_expiry', 'overstock']);
            $table->decimal('current_quantity', 12, 4);
            $table->decimal('threshold_quantity', 12, 4);
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('inventory_adjustment_items');
        Schema::dropIfExists('inventory_adjustments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('units');
    }
};
