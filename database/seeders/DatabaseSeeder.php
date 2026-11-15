<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use App\Models\User;
use App\Services\StockMovementService;
use App\Services\TransferOrderService;
use BcMath\Number;
use Carbon\Carbon;
use Database\Factories\ProductFactory;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $stockService = app(StockMovementService::class);
        $transferService = app(TransferOrderService::class);

        // ─── Roles ─────────────────────────────────────────────
        Role::findOrCreate('admin');
        Role::findOrCreate('branch_manager');
        Role::findOrCreate('staff');

        // ─── Branches ──────────────────────────────────────────
        $downtown = Branch::factory()->create([
            'name' => 'Downtown Branch', 'code' => 'BR-DWT', 'city' => 'Gaza City',
            'address' => ['street' => '12 Omar Al-Mukhtar St'],
        ]);
        $westside = Branch::factory()->create([
            'name' => 'Westside Branch', 'code' => 'BR-WST', 'city' => 'Khan Younis',
            'address' => ['street' => '45 Jalal St'],
        ]);
        $riverside = Branch::factory()->create([
            'name' => 'Riverside Branch', 'code' => 'BR-RVS', 'city' => 'Rafah',
            'address' => ['street' => '78 Al-Rasheed St'],
        ]);

        $branches = collect([$downtown, $westside, $riverside]);

        // ─── Users ─────────────────────────────────────────────
        $admin = User::factory()->create([
            'name' => 'Samir Haddad', 'email' => 'admin@example.com', 'branch_id' => null,
        ]);
        $admin->assignRole('admin');

        $downtownManager = User::factory()->create([
            'name' => 'Layla Odeh', 'email' => 'layla@example.com', 'branch_id' => $downtown->id,
        ]);
        $downtownManager->assignRole('branch_manager');

        $downtownStaff = User::factory()->create([
            'name' => 'Omar Nasser', 'email' => 'omar@example.com', 'branch_id' => $downtown->id,
        ]);
        $downtownStaff->assignRole('staff');

        $westsideManager = User::factory()->create([
            'name' => 'Rania Khalil', 'email' => 'rania@example.com', 'branch_id' => $westside->id,
        ]);
        $westsideManager->assignRole('branch_manager');

        $westsideStaff = User::factory()->create([
            'name' => 'Yousef Barakat', 'email' => 'yousef@example.com', 'branch_id' => $westside->id,
        ]);
        $westsideStaff->assignRole('staff');

        $riversideManager = User::factory()->create([
            'name' => 'Tariq Yousef', 'email' => 'tariq@example.com', 'branch_id' => $riverside->id,
        ]);
        $riversideManager->assignRole('branch_manager');

        $riversideStaff = User::factory()->create([
            'name' => 'Hana Masri', 'email' => 'hana@example.com', 'branch_id' => $riverside->id,
        ]);
        $riversideStaff->assignRole('staff');

        $staffByBranch = [
            $downtown->id => ['manager' => $downtownManager, 'staff' => $downtownStaff],
            $westside->id => ['manager' => $westsideManager, 'staff' => $westsideStaff],
            $riverside->id => ['manager' => $riversideManager, 'staff' => $riversideStaff],
        ];

        // ─── Catalog ───────────────────────────────────────────
        $categories = collect(array_keys(ProductFactory::CATALOG))
            ->mapWithKeys(fn ($name) => [$name => Category::factory()->create(['name' => $name])]);

        $products = collect();

        foreach (ProductFactory::CATALOG as $categoryName => $items) {
            $category = $categories[$categoryName];

            foreach ($items as $itemName) {
                $products->push(Product::factory()->create([
                    'name' => $itemName,
                    'category_id' => $category->id,
                    'unit' => str_contains($itemName, 'kg') ? 'kg'
                        : (str_contains($itemName, 'L') || str_contains($itemName, 'ml') ? 'liters' : 'pcs'),
                ]));
            }
        }

        // ─── Branch Stock + Initial Upload + Sales over 30 days ─
        // Price ranges per category for realism.
        $priceRanges = [
            'Grains' => ['purchase' => [3, 8], 'sale' => [5, 12]],
            'Dairy' => ['purchase' => [1, 5], 'sale' => [2, 8]],
            'Beverages' => ['purchase' => [1, 4], 'sale' => [2, 6]],
            'Produce' => ['purchase' => [1, 3], 'sale' => [2, 5]],
            'Bakery' => ['purchase' => [2, 5], 'sale' => [3, 7]],
            'Cleaning Supplies' => ['purchase' => [3, 8], 'sale' => [5, 12]],
        ];

        foreach ($products as $product) {
            $categoryName = $product->category->name;
            $range = $priceRanges[$categoryName] ?? ['purchase' => [2, 10], 'sale' => [4, 15]];

            foreach ($branches as $branch) {
                // ~85% of products stocked at each branch.
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

                $initialQty = random_int(40, 180);

                $stockService->submit(
                    $product, $branch, $admin,
                    new Number((string) $initialQty),
                    MovementType::InitialStockUpload,
                    note: 'Opening stock',
                );

                // Backdate the initial upload to ~45 days ago.
                $openingDate = now()->subDays(rand(40, 50));
                StockMovement::where('branch_id', $branch->id)
                    ->where('product_id', $product->id)
                    ->where('type', MovementType::InitialStockUpload)
                    ->update(['created_at' => $openingDate]);

                // ── Sales spread over the last 30 days ──
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

                    // Spread across the last 30 days for realistic daily usage.
                    $movement->update(['created_at' => now()->subDays(rand(0, 29))->subHours(rand(8, 18))]);
                }

                // ── Purchase receipt (restock) for ~40% of branch-products ──
                if (random_int(1, 100) <= 40) {
                    $restockQty = random_int(20, 60);

                    $movement = $stockService->submit(
                        $product, $branch, $admin,
                        new Number((string) $restockQty),
                        MovementType::PurchaseReceipt,
                        note: 'Supplier delivery',
                        unitPrice: new Number((string) $purchasePrice),
                    );

                    $movement->update(['created_at' => now()->subDays(rand(5, 20))]);
                }
            }
        }

        // ── A few adjustments to show shrinkage/corrections ──
        $adjustmentStock = BranchStock::where('quantity', '>', 10)->inRandomOrder()->limit(3)->get();

        foreach ($adjustmentStock as $stock) {
            $movement = $stockService->submit(
                $stock->product, $stock->branch, $admin,
                new Number((string) random_int(1, 3)),
                MovementType::AdjustmentOut,
                note: 'Damaged goods written off',
            );
            $movement->update(['created_at' => now()->subDays(rand(1, 10))]);
        }

        $adjustmentInStock = BranchStock::inRandomOrder()->first();
        if ($adjustmentInStock) {
            $movement = $stockService->submit(
                $adjustmentInStock->product, $adjustmentInStock->branch, $admin,
                new Number('2'),
                MovementType::AdjustmentIn,
                note: 'Recount correction — found 2 extra units',
            );
            $movement->update(['created_at' => now()->subDays(rand(1, 5))]);
        }

        // ─── Transfers: realistic mix of statuses and routes ───

        // Helper: find a product stocked at both branches.
        $sharedProduct = function (Branch $from, Branch $to) {
            return BranchStock::where('branch_id', $from->id)
                ->where('quantity', '>', 15)
                ->whereIn('product_id', BranchStock::where('branch_id', $to->id)->pluck('product_id'))
                ->inRandomOrder()
                ->first()?->product;
        };

        // 1) Requested (pending approval) — Downtown → Westside
        $product = $sharedProduct($downtown, $westside);
        if ($product) {
            $transferService->createRequest($downtown, $westside, $downtownStaff, [
                ['product' => $product, 'quantity' => new Number('8')],
            ]);
        }

        // 2) Approved (not yet shipped) — Westside → Riverside
        $product = $sharedProduct($westside, $riverside);
        if ($product) {
            $order = $transferService->createRequest($westside, $riverside, $westsideStaff, [
                ['product' => $product, 'quantity' => new Number('6')],
            ]);
            $transferService->approve($order, $westsideManager);
        }

        // 3) In Transit — Downtown → Riverside
        $product = $sharedProduct($downtown, $riverside);
        if ($product) {
            $order = $transferService->createRequest($downtown, $riverside, $downtownStaff, [
                ['product' => $product, 'quantity' => new Number('10')],
            ]);
            $transferService->approve($order, $downtownManager);
            $transferService->ship($order, $downtownStaff);

            // Backdate for realism.
            $shipDate = now()->subDays(1);
            $order->update(['created_at' => $shipDate->copy()->subDays(2)]);
            StockMovement::where('transfer_order_id', $order->id)->update(['created_at' => $shipDate]);
        }

        // 4-8) Completed transfers with backdated timestamps so
        //       leadTimeDays() has real data across multiple routes.
        $routes = [
            [$downtown, $westside, $downtownStaff, $downtownManager, $westsideManager],
            [$westside, $downtown, $westsideStaff, $westsideManager, $downtownManager],
            [$downtown, $riverside, $downtownStaff, $downtownManager, $riversideManager],
            [$riverside, $downtown, $riversideStaff, $riversideManager, $downtownManager],
            [$westside, $riverside, $westsideStaff, $westsideManager, $riversideManager],
        ];

        foreach ($routes as [$from, $to, $staff, $fromManager, $toManager]) {
            $product = $sharedProduct($from, $to);
            if (! $product) {
                continue;
            }

            $qty = new Number((string) random_int(5, 15));
            $receivedQty = new Number((string) max(1, (int) $qty->sub(random_int(0, 2))->value));

            $order = $transferService->createRequest($from, $to, $staff, [
                ['product' => $product, 'quantity' => $qty],
            ]);
            $transferService->approve($order, $fromManager);
            $transferService->ship($order, $staff);
            $transferService->receive($order, $toManager, [$product->id => $receivedQty]);

            // Backdate: requested ~25 days ago, shipped ~23 days ago, received ~21 days ago.
            // Vary the lead time (1–4 days) so the average isn't flat.
            $daysAgo = random_int(15, 35);
            $leadDays = random_int(1, 4);
            $requestDate = now()->subDays($daysAgo);
            $shipDate = $requestDate->copy()->addDay();
            $receiveDate = $shipDate->copy()->addDays($leadDays);

            $order->update(['created_at' => $requestDate, 'updated_at' => $receiveDate]);

            StockMovement::where('transfer_order_id', $order->id)
                ->where('type', MovementType::TransferOut)
                ->update(['created_at' => $shipDate]);

            StockMovement::where('transfer_order_id', $order->id)
                ->where('type', MovementType::TransferIn)
                ->update(['created_at' => $receiveDate]);
        }

        // 9) Rejected transfer — Riverside → Downtown
        $product = $sharedProduct($riverside, $downtown);
        if ($product) {
            $order = $transferService->createRequest($riverside, $downtown, $riversideStaff, [
                ['product' => $product, 'quantity' => new Number('4')],
            ]);
            $transferService->reject($order);
            $order->update(['created_at' => now()->subDays(8)]);
        }

        // 10) Cancelled transfer — Downtown → Westside
        $product = $sharedProduct($downtown, $westside);
        if ($product) {
            $order = $transferService->createRequest($downtown, $westside, $downtownStaff, [
                ['product' => $product, 'quantity' => new Number('3')],
            ]);
            $transferService->cancel($order);
            $order->update(['created_at' => now()->subDays(5)]);
        }
    }
}
