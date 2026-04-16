<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Categories  (supports parent → child hierarchy)
        // branch_id = null means visible across all branches of the company
        // ---------------------------------------------------------------
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('color', 7)->nullable();            // hex color for POS tile
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'branch_id', 'is_active']);
        });

        // ---------------------------------------------------------------
        // Menu Items
        // ---------------------------------------------------------------
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete(); // null = all branches
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('sku', 50)->nullable()->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->decimal('base_price', 10, 2);             // selling price (BDT)
            $table->decimal('cost_price', 10, 2)->default(0); // for margin calculation
            $table->enum('type', ['food', 'beverage', 'dessert', 'combo', 'other'])->default('food');
            $table->integer('preparation_time')->default(0);   // minutes
            $table->string('unit', 30)->default('plate');      // plate, glass, piece, kg
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('track_inventory')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('gallery')->nullable();               // additional images
            $table->json('tags')->nullable();                  // ['spicy','veg','halal']
            $table->json('allergens')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'branch_id', 'category_id', 'is_available']);
        });

        // ---------------------------------------------------------------
        // Variants  (e.g. Small/Medium/Large)
        // ---------------------------------------------------------------
        Schema::create('menu_item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // Small, Medium, Large
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->string('sku', 50)->nullable()->unique();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('menu_item_id');
        });

        // ---------------------------------------------------------------
        // Modifier Groups  (Add-ons / Toppings / Extras)
        // ---------------------------------------------------------------
        Schema::create('modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // "Extra Toppings", "Spice Level"
            $table->integer('min_selections')->default(0);
            $table->integer('max_selections')->default(1);
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('company_id');
        });

        Schema::create('modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // "Extra Cheese", "Mild", "Hot"
            $table->decimal('price', 10, 2)->default(0);       // additional cost
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('modifier_group_id');
        });

        // ---------------------------------------------------------------
        // Menu Item ↔ Modifier Group  (many-to-many)
        // ---------------------------------------------------------------
        Schema::create('menu_item_modifier_groups', function (Blueprint $table) {
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['menu_item_id', 'modifier_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_modifier_groups');
        Schema::dropIfExists('modifiers');
        Schema::dropIfExists('modifier_groups');
        Schema::dropIfExists('menu_item_variants');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('categories');
    }
};
