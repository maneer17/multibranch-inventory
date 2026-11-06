<?php

namespace Database\Factories;

use App\Models\Model\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<odel=Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'code' => strtoupper($this->faker->unique()->lexify('????')),
            'last_sku_sequence' => 0,
        ];
    }
}
