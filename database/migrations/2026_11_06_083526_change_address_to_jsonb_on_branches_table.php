<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE branches ALTER COLUMN address TYPE jsonb USING address::jsonb');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE branches ALTER COLUMN address TYPE json USING address::json');
    }
};
