<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::factory()->create([
            'name' => 'Downtown Branch', 'code' => 'BR-DWT', 'city' => 'Gaza City',
            'address' => ['street' => '12 Omar Al-Mukhtar St'],
        ]);

        Branch::factory()->create([
            'name' => 'Westside Branch', 'code' => 'BR-WST', 'city' => 'Khan Younis',
            'address' => ['street' => '45 Jalal St'],
        ]);

        Branch::factory()->create([
            'name' => 'Riverside Branch', 'code' => 'BR-RVS', 'city' => 'Rafah',
            'address' => ['street' => '78 Al-Rasheed St'],
        ]);
    }
}
