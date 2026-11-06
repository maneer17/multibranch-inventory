<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Product;
use App\Services\SkuGeneratorService;

class ProductObserver
{
    public function creating(Product $product): void
    {
        // Only generate when nothing was supplied — leaves room for a
        // future deliberate import path to set a real historical SKU,
        // without opening that door to normal creation.
        if (filled($product->sku)) {
            return;
        }

        $category = Category::findOrFail($product->category_id);

        $product->sku = app(SkuGeneratorService::class)->generate($category);
    }
}
