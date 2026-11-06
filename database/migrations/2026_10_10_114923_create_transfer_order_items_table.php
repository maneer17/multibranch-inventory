<?php

// transfer_order_items migration

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_order_items', function (Blueprint $table) {
            $table->id();

            // Cascade is fine here: a line means nothing without its transfer.
            $table->foreignId('transfer_order_id')->constrained('transfer_orders')->cascadeOnDelete();

            // No cascade: deleting a product must not erase transfer lines.
            $table->foreignId('product_id')->constrained('products');

            $table->decimal('quantity_requested', 12, 3);

            // Nullable: null means "hasn't happened yet", 0 would mean
            // "shipped/received zero". Filled at in_transit / received.
            $table->decimal('quantity_sent', 12, 3)->nullable();
            $table->decimal('quantity_received', 12, 3)->nullable();

            $table->timestamps();

            // One line per product per transfer, same idea as branch_stock.
            $table->unique(['transfer_order_id', 'product_id']);
        });

        // A request for zero or negative stock is meaningless.
        DB::statement('ALTER TABLE transfer_order_items ADD CONSTRAINT transfer_order_items_requested_positive CHECK (quantity_requested > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_order_items');
    }
};
