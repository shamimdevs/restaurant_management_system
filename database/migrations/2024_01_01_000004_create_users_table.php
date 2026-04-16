<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete(); // null = all branches
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->string('pin', 6)->nullable();               // quick PIN login for POS
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id']);
            $table->index('email');
        });

        // ---------------------------------------------------------------
        // User → Role assignment  (a user can hold multiple roles,
        // optionally restricted to a specific branch)
        // ---------------------------------------------------------------
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id', 'branch_id']);
            $table->index(['user_id', 'branch_id']);
        });

        // ---------------------------------------------------------------
        // Back-fill manager FK on branches now that users table exists
        // ---------------------------------------------------------------
        Schema::table('branches', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
        });

        // ---------------------------------------------------------------
        // Personal access tokens (Sanctum)
        // ---------------------------------------------------------------
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('users');
    }
};
