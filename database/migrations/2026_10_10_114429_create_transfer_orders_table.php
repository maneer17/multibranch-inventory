<?php

// transfer_orders migration (this file's timestamp must sort BEFORE the items one)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();

            // Default restrict (no cascade): deleting a branch must never
            // silently delete its transfer history.
            $table->foreignId('from_branch_id')->constrained('branches');
            $table->foreignId('to_branch_id')->constrained('branches');

            $table->foreignId('requested_by')->constrained('users');

            // Nullable: nobody has approved a freshly requested transfer.
            $table->foreignId('approved_by')->nullable()->constrained('users');

            // Literal string, not the enum: old migrations must keep running
            // even if the enum is later renamed or moved.
            $table->string('status')->default('requested');

            $table->timestamps();
        });

        // Database-level guard: a transfer can't go from a branch to itself.
        DB::statement('ALTER TABLE transfer_orders ADD CONSTRAINT transfer_orders_different_branches CHECK (from_branch_id <> to_branch_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_orders');
    }
};
