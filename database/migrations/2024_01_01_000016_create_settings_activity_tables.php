<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Settings  (key-value store with type hints)
        // branch_id = null → company-wide; branch_id set → branch override
        // ---------------------------------------------------------------
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('group', 50);                       // general, pos, receipt, sms, email
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'boolean', 'integer', 'float', 'json'])->default('string');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'key']);
            $table->index(['company_id', 'group']);
        });

        // ---------------------------------------------------------------
        // Activity / Audit Log
        // ---------------------------------------------------------------
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);                      // created, updated, deleted, login ...
            $table->string('model_type')->nullable();           // App\Models\Order
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'action']);
            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
        });

        // ---------------------------------------------------------------
        // Notifications
        // ---------------------------------------------------------------
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            // morphs('notifiable') already creates the composite index
        });

        // ---------------------------------------------------------------
        // Reservations  (future table booking)
        // ---------------------------------------------------------------
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('restaurant_table_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reservation_code', 20)->unique();
            $table->string('guest_name');
            $table->string('guest_phone', 20);
            $table->unsignedSmallInteger('pax');
            $table->timestamp('reserved_at');                  // scheduled date & time
            $table->integer('duration_minutes')->default(90);
            $table->enum('status', ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'])
                  ->default('pending');
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'reserved_at', 'status']);
        });

        // ---------------------------------------------------------------
        // Delivery Riders  (internal or third-party)
        // ---------------------------------------------------------------
        Schema::create('riders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('vehicle_type')->nullable();        // motorcycle, bicycle
            $table->string('vehicle_number')->nullable();
            $table->enum('status', ['available', 'on_delivery', 'offline'])->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        // Back-fill FK: orders.rider_id → riders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('rider_id')->references('id')->on('riders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['rider_id']);
        });
        Schema::dropIfExists('riders');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
    }
};
