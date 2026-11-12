<?php
// database/factories/BranchFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        $names = ['Downtown', 'Westside', 'Industrial Park', 'Old City', 'Riverside', 'North Gate'];
        $name = $this->faker->unique()->randomElement($names);

        return [
            'code' => 'BR-'.strtoupper(substr($name, 0, 3)),
            'name' => "{$name} Branch",
            'address' => ['street' => $this->faker->streetAddress()],
            'city' => $this->faker->city(),
            'is_active' => true,
        ];
    }
}
