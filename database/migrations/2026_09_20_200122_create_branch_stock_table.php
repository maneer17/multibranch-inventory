<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->decimal('sale_price', 10, 2);
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('reorder_point', 12, 3)->nullable();
            $table->decimal('reorder_qty', 12, 3)->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
        });
        DB::statement('ALTER TABLE branch_stock ADD CONSTRAINT branch_stock_quantity_non_negative CHECK (quantity >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_stock');
    }
};
