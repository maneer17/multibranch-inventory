<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE transfer_orders
    ADD CONSTRAINT transfer_orders_requester_not_approver
        CHECK (requested_by IS DISTINCT FROM approved_by)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE transfer_orders DROP CONSTRAINT transfer_orders_requester_not_approver');
    }
};
