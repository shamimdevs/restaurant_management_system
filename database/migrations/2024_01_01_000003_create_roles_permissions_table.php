<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Roles  (system roles scoped per company)
        // ---------------------------------------------------------------
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // Admin, Manager, Cashier, Waiter, Kitchen, Delivery
            $table->string('slug');                            // admin, manager, cashier, waiter, kitchen, delivery
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);      // system roles cannot be deleted
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index('company_id');
        });

        // ---------------------------------------------------------------
        // Permissions  (module + action pairs, seeded once globally)
        // ---------------------------------------------------------------
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module');                          // pos, inventory, accounting, hr, reports ...
            $table->string('action');                          // view, create, edit, delete, export
            $table->string('name')->unique();                  // pos.view, inventory.create …
            $table->string('display_name')->nullable();
            $table->timestamps();

            $table->index(['module', 'action']);
        });

        // ---------------------------------------------------------------
        // Role → Permission  pivot
        // ---------------------------------------------------------------
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
