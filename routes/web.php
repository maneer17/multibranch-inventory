<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'stats' => [
            'products' => Product::count(),
            'branches' => Branch::where('is_active', true)->count(),
            'movements' => StockMovement::count(),
            'transfers' => TransferOrder::count(),
        ],
    ]);
});

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'ar'])) {
        session(['locale' => $locale]);
        cookie()->queue('locale', $locale, 60 * 24 * 365);
    }

    return redirect()->back();
})->name('locale.switch');
