<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bangladesh VAT system:
     *  - Standard VAT: 15%
     *  - Restaurant VAT: 5% (truncated rate under SRO)
     *  - Service Charge: 10% (hotel/restaurant)
     *  - SD (Supplementary Duty): varies
     *  All rates are configurable per item / category.
     */
    public function up(): void
    {
        Schema::create('tax_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // e.g. "Restaurant VAT 5%", "Exempt"
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // VAT, Service Charge, SD
            $table->enum('type', ['vat', 'service_charge', 'supplementary_duty', 'other']);
            $table->decimal('rate', 5, 2);                    // e.g. 5.00, 7.50, 10.00, 15.00
            $table->boolean('is_inclusive')->default(false);  // price already includes tax
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tax_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_groups');
    }
};
