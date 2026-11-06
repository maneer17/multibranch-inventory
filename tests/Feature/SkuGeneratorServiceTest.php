<?php
use App\Models\Category;
use App\Models\Product;
use App\Services\SkuGeneratorService;
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
it('generates the first SKU as sequence 1', function () {
    $category = Category::factory()->create(['code' => 'GRAIN']);
    $service = new SkuGeneratorService();
    $sku = $service->generate($category);
    expect($sku)->toBe('GRAIN-000001');
});
it('increments the sequence on each call', function () {
    $category = Category::factory()->create(['code' => 'GRAIN']);
    $service = new SkuGeneratorService();
    $sku = $service->generate($category);
    expect($sku)->toBe('GRAIN-000001');
    $sku2 = $service->generate($category);
    expect($sku2)->toBe('GRAIN-000002');
});
it('keeps each category sequence independent under interleaved calls', function () {
    $grain = Category::factory()->create(['code' => 'GRAIN']);
    $dairy = Category::factory()->create(['code' => 'DAIRY']);
    $service = new SkuGeneratorService();

    $grainSku1 = $service->generate($grain);
    $dairySku1 = $service->generate($dairy);
    $grainSku2 = $service->generate($grain);
    $dairySku2 = $service->generate($dairy);

    expect($grainSku1)->toBe('GRAIN-000001');
    expect($dairySku1)->toBe('DAIRY-000001');
    expect($grainSku2)->toBe('GRAIN-000002');
    expect($dairySku2)->toBe('DAIRY-000002');
});
it('generates a sku via the observer when none is supplied', function () {
    $category = Category::factory()->create(['code' => 'GRAIN']);

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'sku' => null,
    ]);

    expect($product->sku)->toBe('GRAIN-000001');
});

it('respects a supplied sku and does not override it', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'sku' => 'CUSTOM-SKU',
    ]);

    expect($product->sku)->toBe('CUSTOM-SKU');
});
