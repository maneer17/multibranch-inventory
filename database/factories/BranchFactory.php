<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('BR-???')),
            'name' => $this->faker->city().' Branch',
            'address' => ['street' => $this->faker->streetAddress()],
            'city' => $this->faker->city(),
            'is_active' => true,
        ];
    }
}
