<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public const CATALOG = [
        'Grains' => ['Basmati Rice 5kg', 'Whole Wheat Flour 10kg', 'Rolled Oats 1kg', 'Pasta Penne 500g'],
        'Dairy' => ['Milk 1L', 'Cheddar Cheese 400g', 'Greek Yogurt 500g', 'Butter 250g'],
        'Beverages' => ['Orange Juice 1L', 'Sparkling Water 1.5L', 'Cola 500ml', 'Black Tea 100 bags'],
        'Produce' => ['Tomatoes 1kg', 'Onions 1kg', 'Potatoes 2kg', 'Bananas 1kg'],
        'Bakery' => ['White Bread Loaf', 'Croissants 6pk', 'Pita Bread 10pk'],
        'Cleaning Supplies' => ['Dish Soap 750ml', 'All-Purpose Cleaner 1L', 'Paper Towels 6pk'],
    ];

    public function definition(): array
    {
        $category = Category::inRandomOrder()->first() ?? Category::factory()->create();

        return [
            // sku intentionally omitted — ProductObserver generates it
            'name' => $this->faker->words(2, true),
            'category_id' => $category->id,
            'unit' => $this->faker->randomElement(['kg', 'pcs', 'liters', 'meters']),
            'is_active' => true,
        ];
    }
}
