<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'name' => $this->faker->words(2, true),
            'category_id' => Category::factory(),
            'unit' => $this->faker->randomElement(['kg', 'pcs', 'liters', 'meters']),
            'is_active' => true,
        ];
    }
}
