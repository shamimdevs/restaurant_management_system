<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Departments & Designations
        // ---------------------------------------------------------------
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // Kitchen, Service, Management
            $table->timestamps();

            $table->index('branch_id');
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // Head Chef, Sous Chef, Cashier ...
            $table->timestamps();

            $table->index('company_id');
        });

        // ---------------------------------------------------------------
        // Employees  (linked to a system user)
        // ---------------------------------------------------------------
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // system login
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_id', 30)->unique();       // EMP-DHK-001
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('nid_number', 30)->nullable();      // Bangladesh NID
            $table->text('address')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable()->default('Dhaka');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date');
            $table->date('end_date')->nullable();              // termination/resignation
            $table->string('photo')->nullable();
            $table->json('emergency_contact')->nullable();     // {name, phone, relation}
            $table->enum('salary_type', ['monthly', 'daily', 'hourly'])->default('monthly');
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->json('bank_account')->nullable();          // {bank, account_no, routing}
            $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
        });

        // ---------------------------------------------------------------
        // Attendance
        // ---------------------------------------------------------------
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('hours_worked', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'holiday', 'leave', 'off'])
                  ->default('present');
            $table->string('check_in_method')->nullable();     // manual, biometric, qr
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index(['branch_id', 'date']);
        });

        // ---------------------------------------------------------------
        // Leave Management
        // ---------------------------------------------------------------
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // Sick, Annual, Casual, Maternity
            $table->integer('days_allowed_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedSmallInteger('days');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        // ---------------------------------------------------------------
        // Salary Structure & Payment
        // ---------------------------------------------------------------
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic_salary', 10, 2);
            $table->json('allowances')->nullable();            // [{name:"House Rent",amount:3000},...]
            $table->json('deductions')->nullable();            // [{name:"PF",amount:500},...]
            $table->decimal('gross_salary', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index('employee_id');
        });

        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payroll_reference', 30)->unique(); // SAL-DHK001-2024-01
            $table->unsignedTinyInteger('month');              // 1-12
            $table->unsignedSmallInteger('year');
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('total_allowances', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('overtime_pay', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('tax_deduction', 10, 2)->default(0);  // Income tax (TDS)
            $table->decimal('net_salary', 10, 2);
            $table->unsignedSmallInteger('working_days')->default(0);
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'bkash', 'nagad'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'pending', 'paid', 'cancelled'])->default('draft');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
            $table->index(['branch_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
    }
};
