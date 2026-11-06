<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // Stored directly (not via branch_stock's id) so history survives
            // a branch_stock row being deleted.
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('product_id')->constrained();

            // Signed: negative = stock leaving, positive = stock arriving.
            // decimal, not float: exact math, so SUM(quantity) can be compared
            // to branch_stock.quantity without rounding drift.
            $table->decimal('quantity', 12, 3);

            // Cast to the MovementType enum in the model.
            $table->string('type');

            // Which transfer/PO caused this. Null for adjustments and sales.
            // Also creates an index on (reference_type, reference_id).
            // querying "Show me all stock movements related to this Purchase Order" will be lightning fast.
            $table->foreignId('transfer_order_id')->nullable()->constrained();

            $table->text('note')->nullable();

            $table->foreignId('created_by')->constrained('users');

            // Append-only table: rows are never updated, so no updated_at.
            $table->timestamp('created_at')->useCurrent();

            // Serves "movements for this product at this branch, newest first".
            $table->index(['branch_id', 'product_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
