<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Customers  (CRM)
        // ---------------------------------------------------------------
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('anniversary_date')->nullable();
            $table->text('address')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable()->default('Dhaka');
            $table->string('avatar')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->unsignedInteger('visit_count')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->enum('segment', ['new', 'regular', 'vip', 'churned'])->default('new');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'phone']);
            $table->index(['company_id', 'segment']);
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->default('home');  // home, work, other
            $table->text('address');
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->string('landmark')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('customer_id');
        });

        // ---------------------------------------------------------------
        // Loyalty Program configuration
        // ---------------------------------------------------------------
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('points_per_currency', 8, 2)->default(1);  // 1 pt per 10 BDT
            $table->decimal('currency_per_point', 8, 2)->default(10);  // spend 10 BDT to earn 1 pt
            $table->integer('min_redeem_points')->default(100);          // min points to redeem
            $table->decimal('redeem_value', 8, 2)->default(1);          // 100 pts = 10 BDT (so 1 pt = 0.10 BDT)
            $table->decimal('min_order_for_earn', 10, 2)->default(0);
            $table->integer('point_expiry_days')->nullable();            // null = never expire
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable();         // FK set after orders table
            $table->enum('type', ['earned', 'redeemed', 'expired', 'adjusted', 'bonus']);
            $table->integer('points');                                   // positive = earned, negative = redeemed
            $table->integer('balance_after');
            $table->text('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'type']);
        });

        // ---------------------------------------------------------------
        // Customer Feedback
        // ---------------------------------------------------------------
        Schema::create('customer_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedTinyInteger('overall_rating');              // 1-5
            $table->unsignedTinyInteger('food_rating')->nullable();
            $table->unsignedTinyInteger('service_rating')->nullable();
            $table->unsignedTinyInteger('ambiance_rating')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('responded_at')->nullable();
            $table->text('response')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'overall_rating']);
        });

        // Back-fill FK: table_sessions.customer_id → customers
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
        Schema::dropIfExists('customer_feedback');
        Schema::dropIfExists('customer_point_transactions');
        Schema::dropIfExists('loyalty_programs');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
