<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kitchen Display System (KDS)
     * A kitchen ticket is generated per order (or per kitchen station).
     * Items within the ticket track their own cooking status.
     */
    public function up(): void
    {
        Schema::create('kitchen_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number', 20);               // daily sequential: #001
            $table->string('table_label')->nullable();          // "T-05 / Takeaway"
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery', 'qr_order']);
            $table->enum('status', ['pending', 'cooking', 'ready', 'served', 'cancelled'])
                  ->default('pending');
            $table->unsignedTinyInteger('priority')->default(5); // 1=urgent, 10=low
            $table->text('notes')->nullable();
            $table->string('station')->nullable();             // "grill", "fry", "salad"
            $table->timestamp('cooking_started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->unsignedSmallInteger('bump_count')->default(0); // times bumped from screen
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'created_at']);
            $table->unique(['branch_id', 'ticket_number']);
        });

        Schema::create('kitchen_ticket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');                        // snapshot
            $table->string('variant_name')->nullable();
            $table->json('modifiers')->nullable();              // [{"name":"Extra Cheese","qty":1}]
            $table->unsignedSmallInteger('quantity');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'cooking', 'ready', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('kitchen_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_ticket_items');
        Schema::dropIfExists('kitchen_tickets');
    }
};
