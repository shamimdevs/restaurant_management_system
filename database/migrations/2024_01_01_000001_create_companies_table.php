<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('vat_registration_no')->nullable();   // BIN (Business Identification Number)
            $table->string('trade_license_no')->nullable();
            $table->string('currency', 10)->default('BDT');
            $table->string('currency_symbol', 5)->default('৳');
            $table->string('timezone')->default('Asia/Dhaka');
            $table->string('date_format')->default('d/m/Y');
            $table->string('time_format')->default('h:i A');
            $table->string('fiscal_year_start')->default('01-01'); // MM-DD
            $table->json('receipt_settings')->nullable();          // header, footer, show_vat, etc.
            $table->json('notification_settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
