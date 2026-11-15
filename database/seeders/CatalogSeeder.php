<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Database\Factories\ProductFactory;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProductFactory::CATALOG as $categoryName => $items) {
            $category = Category::factory()->create(['name' => $categoryName]);

            foreach ($items as $itemName) {
                Product::factory()->create([
                    'name' => $itemName,
                    'category_id' => $category->id,
                    'unit' => str_contains($itemName, 'kg') ? 'kg'
                        : (str_contains($itemName, 'L') || str_contains($itemName, 'ml') ? 'liters' : 'pcs'),
                ]);
            }
        }
    }
}
