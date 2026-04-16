<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Promotions  (campaign-level rules)
        // ---------------------------------------------------------------
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete(); // null = all branches
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->enum('type', [
                'percentage_discount',  // X% off total
                'fixed_discount',       // X BDT off total
                'buy_x_get_y',          // Buy X items, get Y free/discounted
                'free_item',            // Free item on qualifying order
                'happy_hour',           // Time-based discount
                'combo',                // Fixed combo price
            ]);
            $table->decimal('value', 10, 2)->default(0);         // % or BDT amount
            $table->decimal('min_order_value', 10, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->nullable();   // cap for % discounts
            $table->enum('applicable_to', ['all', 'category', 'item'])->default('all');
            $table->json('applicable_ids')->nullable();           // category/item IDs
            $table->json('excluded_ids')->nullable();             // items excluded from promo
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();               // for happy hour
            $table->time('end_time')->nullable();
            $table->json('days_of_week')->nullable();             // [0,1,2,...,6] Sun=0
            $table->unsignedInteger('usage_limit')->nullable();   // total uses (null = unlimited)
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->boolean('is_stackable')->default(false);      // can combine with coupons?
            $table->integer('priority')->default(0);              // higher = applied first
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id', 'is_active']);
        });

        // ---------------------------------------------------------------
        // Buy-X-Get-Y rules linked to a promotion
        // ---------------------------------------------------------------
        Schema::create('promotion_bxgy_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buy_menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->unsignedInteger('buy_quantity');
            $table->foreignId('get_menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->unsignedInteger('get_quantity');
            $table->decimal('get_discount_percentage', 5, 2)->default(100); // 100 = free
            $table->timestamps();
        });

        // ---------------------------------------------------------------
        // Coupons  (user-entered codes, can be linked to a promotion)
        // ---------------------------------------------------------------
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_order_value', 10, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'code', 'is_active']);
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id');              // FK after orders table
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamp('used_at')->useCurrent();

            $table->index(['coupon_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('promotion_bxgy_rules');
        Schema::dropIfExists('promotions');
    }
};
