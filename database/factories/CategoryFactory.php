<?php
// database/factories/CategoryFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $categories = [
            'Grains' => 'GRAIN',
            'Dairy' => 'DAIRY',
            'Beverages' => 'BEV',
            'Produce' => 'PROD',
            'Bakery' => 'BAKE',
            'Cleaning Supplies' => 'CLEAN',
        ];
        $name = $this->faker->unique()->randomElement(array_keys($categories));

        return [
            'name' => $name,
            'code' => $categories[$name],
            'last_sku_sequence' => 0,
        ];
    }
}
