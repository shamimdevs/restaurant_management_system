<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();              // e.g. DHK-001, CTG-001
            $table->text('address')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->json('working_days')->nullable();          // ["Mon","Tue",...,"Sun"]
            $table->unsignedBigInteger('manager_id')->nullable(); // FK set after users table
            $table->integer('table_count')->default(0);
            $table->json('settings')->nullable();              // branch-specific overrides
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
