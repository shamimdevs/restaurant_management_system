<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Orders  (POS / QR / Online)
        // ---------------------------------------------------------------
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();          // RMS-DHK001-20240101-0001
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();     // cashier / waiter
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();

            // Order type & status
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery', 'qr_order'])->default('dine_in');
            $table->enum('status', [
                'pending',      // just created / QR placed
                'confirmed',    // accepted by staff
                'preparing',    // kitchen working on it
                'ready',        // kitchen done
                'served',       // delivered to table
                'completed',    // paid & closed
                'cancelled',
            ])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');

            // Financial columns (all in BDT)
            $table->decimal('subtotal', 12, 2)->default(0);        // sum of item totals before tax/disc
            $table->decimal('discount_amount', 12, 2)->default(0); // promotion/manual discount
            $table->string('discount_label')->nullable();           // "Happy Hour 20%"
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);    // final payable
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->decimal('loyalty_points_used', 10, 2)->default(0); // BDT value of redeemed pts
            $table->integer('loyalty_points_earned')->default(0);

            // Delivery info
            $table->text('delivery_address')->nullable();
            $table->string('delivery_area')->nullable();
            $table->unsignedBigInteger('rider_id')->nullable();     // FK to employees
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Split bill support
            $table->boolean('is_split')->default(false);
            $table->unsignedBigInteger('parent_order_id')->nullable(); // original order before split

            $table->enum('source', ['pos', 'qr', 'online', 'phone'])->default('pos');
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status', 'order_type']);
            $table->index(['branch_id', 'created_at']);            // for daily reports
            $table->index(['company_id', 'created_at']);
            $table->index('customer_id');
            $table->foreign('parent_order_id')->references('id')->on('orders')->nullOnDelete();
        });

        // ---------------------------------------------------------------
        // Order Items
        // ---------------------------------------------------------------
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('menu_item_variants')->nullOnDelete();
            $table->string('item_name');                           // snapshot at time of order
            $table->string('variant_name')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);     // VAT on this item
            $table->decimal('subtotal', 10, 2);                   // unit_price * qty
            $table->decimal('total', 10, 2);                      // subtotal - discount + tax
            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->boolean('is_complimentary')->default(false);  // waived / free
            $table->timestamps();

            $table->index('order_id');
            $table->index('menu_item_id');
        });

        // ---------------------------------------------------------------
        // Order Item Modifiers  (snapshot of chosen add-ons)
        // ---------------------------------------------------------------
        Schema::create('order_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modifier_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('modifier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('group_name');                          // snapshot
            $table->string('modifier_name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();

            $table->index('order_item_id');
        });

        // ---------------------------------------------------------------
        // Order Tax Breakdown  (for VAT report per order)
        // ---------------------------------------------------------------
        Schema::create('order_tax_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tax_name');                            // snapshot
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->timestamps();

            $table->index('order_id');
        });

        // ---------------------------------------------------------------
        // Payments  (one order can have multiple partial payments)
        // ---------------------------------------------------------------
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('payment_method', [
                'cash', 'card', 'bkash', 'nagad', 'rocket',
                'upay', 'tap', 'bank_transfer', 'credit', 'loyalty_points',
            ]);
            $table->decimal('amount', 12, 2);
            $table->string('reference_number')->nullable();        // bKash trxID, card last4
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->json('gateway_response')->nullable();          // raw MFS response
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['branch_id', 'payment_method']);
        });

        // ---------------------------------------------------------------
        // Back-fill FKs from earlier tables that reference orders
        // ---------------------------------------------------------------
        Schema::table('customer_point_transactions', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::table('customer_feedback', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['customer_id']);
        });
        Schema::table('customer_feedback', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
        Schema::table('customer_point_transactions', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_tax_details');
        Schema::dropIfExists('order_item_modifiers');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
