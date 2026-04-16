<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Floor Plans  (e.g. Ground Floor, Rooftop, VIP Section)
        // ---------------------------------------------------------------
        Schema::create('floor_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();           // floor layout image
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('branch_id');
        });

        // ---------------------------------------------------------------
        // Restaurant Tables
        // ---------------------------------------------------------------
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('floor_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('table_number', 20);            // "T-01", "VIP-03"
            $table->string('name', 100)->nullable();       // "Window Seat"
            $table->unsignedTinyInteger('capacity')->default(4);
            $table->enum('shape', ['square', 'round', 'rectangle'])->default('square');
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance'])
                  ->default('available');
            $table->string('qr_code', 100)->unique();      // unique token for QR URL
            $table->string('qr_image_path')->nullable();   // stored QR image
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'table_number']);
            $table->index(['branch_id', 'status']);
        });

        // ---------------------------------------------------------------
        // Table Sessions  (one session per table sitting)
        // ---------------------------------------------------------------
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('waiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable(); // FK added after customers table
            $table->unsignedSmallInteger('pax')->default(1); // number of guests
            $table->enum('status', ['active', 'billed', 'closed'])->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('restaurant_table_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_sessions');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('floor_plans');
    }
};
