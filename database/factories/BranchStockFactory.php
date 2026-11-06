<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchStockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'quantity' =>(string) $this->faker->randomFloat(3, 0, 100),
            'sale_price' => (string) $this->faker->randomFloat(2, 1, 50),
            'purchase_price' => (string) $this->faker->randomFloat(2, 1, 50),
            'reorder_point' => null,
            'reorder_qty' => null,
        ];
    }
}
