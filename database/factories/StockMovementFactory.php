<?php

namespace Database\Factories;

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'created_by' => User::factory(),
            'quantity' => '-1.000',
            'type' => MovementType::Sale,
        ];
    }
}
