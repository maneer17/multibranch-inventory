<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\StoreProductRequest;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\SkuGeneratorService;

class ProductController extends Controller
{
    public function __construct(
        private SkuGeneratorService $skuGenerator
    ) {}

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $category = Category::where('id', $data['category_id'])->first();
        $data['sku'] = $this->skuGenerator->generate($category);
        $product = Product::create($data);
        return response()->json($product, 201);
    }
    public function index()
    {

    }
}
