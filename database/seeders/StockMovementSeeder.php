<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockMovementService;
use BcMath\Number;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    private const PRICE_RANGES = [
        'Grains' => ['purchase' => [3, 8], 'sale' => [5, 12]],
        'Dairy' => ['purchase' => [1, 5], 'sale' => [2, 8]],
        'Beverages' => ['purchase' => [1, 4], 'sale' => [2, 6]],
        'Produce' => ['purchase' => [1, 3], 'sale' => [2, 5]],
        'Bakery' => ['purchase' => [2, 5], 'sale' => [3, 7]],
        'Cleaning Supplies' => ['purchase' => [3, 8], 'sale' => [5, 12]],
    ];

    public function run(): void
    {
        $stockService = app(StockMovementService::class);
        $admin = User::where('email', 'admin@example.com')->first();
        $branches = Branch::all();
        $products = Product::with('category')->get();

        $this->seedBranchStock($stockService, $admin, $branches, $products);
        $this->seedAdjustments($stockService, $admin);
        $this->seedReorderCandidates($stockService, $admin);
    }

    private function seedBranchStock(
        StockMovementService $stockService,
        User $admin,
        $branches,
        $products,
    ): void {
        foreach ($products as $product) {
            $range = self::PRICE_RANGES[$product->category->name]
                ?? ['purchase' => [2, 10], 'sale' => [4, 15]];

            foreach ($branches as $branch) {
                if (random_int(1, 100) > 85) {
                    continue;
                }

                $purchasePrice = round(rand($range['purchase'][0] * 100, $range['purchase'][1] * 100) / 100, 2);
                $salePrice = round($purchasePrice * (1 + rand(25, 60) / 100), 2);

                BranchStock::create([
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'quantity' => '0',
                    'sale_price' => (string) $salePrice,
                    'purchase_price' => (string) $purchasePrice,
                    'reorder_point' => (string) round(rand(500, 2000) / 100, 3),
                    'reorder_qty' => (string) round(rand(2000, 6000) / 100, 3),
                ]);

                $this->seedInitialStock($stockService, $product, $branch, $admin);
                $this->seedSales($stockService, $product, $branch, $admin, $salePrice);
                $this->seedPurchaseReceipt($stockService, $product, $branch, $admin, $purchasePrice);
            }
        }
    }

    private function seedInitialStock(
        StockMovementService $stockService,
        Product $product,
        Branch $branch,
        User $admin,
    ): void {
        $stockService->submit(
            $product, $branch, $admin,
            new Number((string) random_int(40, 180)),
            MovementType::InitialStockUpload,
            note: 'Opening stock',
        );

        StockMovement::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->where('type', MovementType::InitialStockUpload)
            ->update(['created_at' => now()->subDays(rand(40, 50))]);
    }

    private function seedSales(
        StockMovementService $stockService,
        Product $product,
        Branch $branch,
        User $admin,
        float $salePrice,
    ): void {
        $saleCount = random_int(3, 12);

        for ($i = 0; $i < $saleCount; $i++) {
            $saleQty = random_int(1, 6);

            $currentStock = BranchStock::where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->first();

            if ($currentStock->quantity->compare($saleQty) < 0) {
                break;
            }

            $movement = $stockService->submit(
                $product, $branch, $admin,
                new Number((string) $saleQty),
                MovementType::Sale,
                note: 'Walk-in sale',
                unitPrice: new Number((string) $salePrice),
            );

            $movement->update([
                'created_at' => now()->subDays(rand(0, 29))->subHours(rand(8, 18)),
            ]);
        }
    }

    private function seedPurchaseReceipt(
        StockMovementService $stockService,
        Product $product,
        Branch $branch,
        User $admin,
        float $purchasePrice,
    ): void {
        if (random_int(1, 100) > 40) {
            return;
        }

        $movement = $stockService->submit(
            $product, $branch, $admin,
            new Number((string) random_int(20, 60)),
            MovementType::PurchaseReceipt,
            note: 'Supplier delivery',
            unitPrice: new Number((string) $purchasePrice),
        );

        $movement->update(['created_at' => now()->subDays(rand(5, 20))]);
    }

    /**
     * Drain a few branch-stock rows below their reorder point so the
     * ReorderDashboard has suggestions to show on first login.
     */
    private function seedReorderCandidates(StockMovementService $stockService, User $admin): void
    {
        // Pick 3–4 rows that still have decent stock and whose product exists
        // in at least one other branch (so a source candidate exists).
        $candidates = BranchStock::with(['product', 'branch'])
            ->where('quantity', '>', 20)
            ->where('reorder_point', '>', 0)
            ->get()
            ->filter(function (BranchStock $stock) {
                return BranchStock::where('product_id', $stock->product_id)
                    ->where('branch_id', '!=', $stock->branch_id)
                    ->where('quantity', '>', 30)
                    ->exists();
            })
            ->shuffle()
            ->take(4);

        foreach ($candidates as $stock) {
            // Sell enough to drop well below the reorder point.
            // Target: leave ~30-60% of the reorder point.
            $target = (new Number($stock->reorder_point->value))
                ->mul(new Number('0.' . random_int(3, 6)));
            $toSell = $stock->quantity->sub($target);

            if ($toSell->compare(1) < 0) {
                continue;
            }

            // Spread across 6–10 sales in the last 30 days (≥5 needed for trust).
            $saleCount = random_int(6, 10);
            $perSale = $toSell->div($saleCount, 3);

            $salePrice = $stock->sale_price ?? new Number('5.00');

            for ($i = 0; $i < $saleCount; $i++) {
                $current = BranchStock::find($stock->id);
                if ($current->quantity->compare($perSale) < 0) {
                    break;
                }

                $movement = $stockService->submit(
                    $stock->product, $stock->branch, $admin,
                    $perSale,
                    MovementType::Sale,
                    note: 'High-demand sale',
                    unitPrice: $salePrice,
                );

                $movement->update([
                    'created_at' => now()->subDays(rand(0, 28))->subHours(rand(8, 18)),
                ]);
            }
        }
    }

    private function seedAdjustments(StockMovementService $stockService, User $admin): void
    {
        $writeOffs = BranchStock::with(['product', 'branch'])
            ->where('quantity', '>', 10)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        foreach ($writeOffs as $stock) {
            $movement = $stockService->submit(
                $stock->product, $stock->branch, $admin,
                new Number((string) random_int(1, 3)),
                MovementType::AdjustmentOut,
                note: 'Damaged goods written off',
            );
            $movement->update(['created_at' => now()->subDays(rand(1, 10))]);
        }

        $correction = BranchStock::with(['product', 'branch'])->inRandomOrder()->first();

        if ($correction) {
            $movement = $stockService->submit(
                $correction->product, $correction->branch, $admin,
                new Number('2'),
                MovementType::AdjustmentIn,
                note: 'Recount correction — found 2 extra units',
            );
            $movement->update(['created_at' => now()->subDays(rand(1, 5))]);
        }
    }
}
