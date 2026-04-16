<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Suppliers
        // ---------------------------------------------------------------
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->string('vat_registration_no')->nullable();    // supplier's BIN
            $table->json('bank_details')->nullable();             // {bank, account, routing}
            $table->decimal('balance', 12, 2)->default(0);       // positive = we owe them
            $table->integer('credit_days')->default(0);          // payment terms
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
        });

        // ---------------------------------------------------------------
        // Purchase Orders
        // ---------------------------------------------------------------
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('po_number', 30)->unique();          // PO-DHK001-20240101-001
            $table->enum('status', ['draft', 'sent', 'partial', 'received', 'cancelled'])->default('draft');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('other_charges', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['supplier_id', 'payment_status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 10, 4);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('total_price', 12, 2);
            $table->decimal('received_quantity', 12, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
        });

        // ---------------------------------------------------------------
        // Supplier Payments
        // ---------------------------------------------------------------
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'bkash', 'nagad']);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->date('paid_date');
            $table->foreignId('paid_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'paid_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
