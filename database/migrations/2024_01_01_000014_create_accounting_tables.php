<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Double-entry accounting system.
     *
     * Chart of Accounts hierarchy:
     *   Group (Asset / Liability / Equity / Income / Expense)
     *     └─ Account (Bank, Cash, VAT Payable, Sales Revenue …)
     *
     * Every financial event creates a Journal Entry with balanced
     * debit / credit lines (∑ debits = ∑ credits).
     */
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Account Groups  (standard accounting categories)
        // ---------------------------------------------------------------
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);  // asset+expense=debit, others=credit
            $table->boolean('is_system')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('account_groups')->nullOnDelete();
            $table->index(['company_id', 'type']);
        });

        // ---------------------------------------------------------------
        // Accounts  (Chart of Accounts leaf nodes)
        // ---------------------------------------------------------------
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();              // 1001, 2001 …
            $table->text('description')->nullable();
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);  // maintained via triggers/service
            $table->boolean('is_system')->default(false);      // cash, vat payable, sales etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'type', 'is_active']);
        });

        // ---------------------------------------------------------------
        // Journal Entries
        // ---------------------------------------------------------------
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('entry_number', 30)->unique();      // JE-2024-000001
            $table->date('entry_date');
            $table->text('description')->nullable();
            $table->nullableMorphs('reference');               // order, expense, purchase_order …
            $table->enum('status', ['draft', 'posted', 'voided'])->default('posted');
            $table->decimal('total_debit', 15, 2)->default(0);  // for quick validation
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'entry_date']);
            // nullableMorphs('reference') already creates the composite index on reference_type + reference_id
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_id');
        });

        // ---------------------------------------------------------------
        // Expense Categories
        // ---------------------------------------------------------------
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });

        // ---------------------------------------------------------------
        // Expenses
        // ---------------------------------------------------------------
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_no', 30)->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->text('description');
            $table->string('paid_to')->nullable();              // vendor name
            $table->string('receipt_image')->nullable();
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'card', 'cheque', 'bkash', 'nagad']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'expense_date']);
            $table->index(['branch_id', 'expense_category_id']);
        });

        // ---------------------------------------------------------------
        // Cash Register  (per shift / per branch)
        // ---------------------------------------------------------------
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->decimal('expected_balance', 12, 2)->nullable();  // calculated
            $table->decimal('difference', 12, 2)->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        // ---------------------------------------------------------------
        // VAT Returns / Reports
        // ---------------------------------------------------------------
        Schema::create('vat_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_sales', 14, 2)->default(0);
            $table->decimal('taxable_sales', 14, 2)->default(0);
            $table->decimal('exempt_sales', 14, 2)->default(0);
            $table->decimal('total_vat_collected', 12, 2)->default(0);  // output VAT
            $table->decimal('total_vat_paid', 12, 2)->default(0);       // input VAT (from purchases)
            $table->decimal('net_vat_payable', 12, 2)->default(0);      // collected - paid
            $table->decimal('service_charge_collected', 12, 2)->default(0);
            $table->enum('status', ['draft', 'filed', 'amended'])->default('draft');
            $table->timestamp('filed_at')->nullable();
            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filing_reference')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_reports');
        Schema::dropIfExists('cash_registers');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('account_groups');
    }
};
