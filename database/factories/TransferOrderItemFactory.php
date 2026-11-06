<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\TransferOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transfer_order_id' => TransferOrder::factory(),
            'product_id' => Product::factory(),
            'quantity_requested' => (string) $this->faker->randomFloat(3, 1, 50),
            'quantity_sent' => null,
            'quantity_received' => null,
        ];
    }
}
