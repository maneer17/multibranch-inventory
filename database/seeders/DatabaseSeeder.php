<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StockMovementService;
use App\Services\TransferOrderService;
use BcMath\Number;
use Database\Factories\ProductFactory;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Roles ---
        Role::findOrCreate('admin');
        Role::findOrCreate('branch_manager');
        Role::findOrCreate('staff');

        // --- Branches ---
        $downtown = Branch::factory()->create(['name' => 'Downtown Branch', 'code' => 'BR-DWT', 'city' => 'Gaza City']);
        $westside = Branch::factory()->create(['name' => 'Westside Branch', 'code' => 'BR-WST', 'city' => 'Khan Younis']);
        $riverside = Branch::factory()->create(['name' => 'Riverside Branch', 'code' => 'BR-RVS', 'city' => 'Rafah']);

        // --- Users ---
        $admin = User::factory()->create(['name' => 'Samir Haddad', 'email' => 'admin@example.com', 'branch_id' => null]);
        $admin->assignRole('admin');

        $downtownManager = User::factory()->create(['name' => 'Layla Odeh', 'email' => 'layla@example.com', 'branch_id' => $downtown->id]);
        $downtownManager->assignRole('branch_manager');

        $downtownStaff = User::factory()->create(['name' => 'Omar Nasser', 'email' => 'omar@example.com', 'branch_id' => $downtown->id]);
        $downtownStaff->assignRole('staff');

        $westsideManager = User::factory()->create(['name' => 'Rania Khalil', 'email' => 'rania@example.com', 'branch_id' => $westside->id]);
        $westsideManager->assignRole('branch_manager');

        $riversideManager = User::factory()->create(['name' => 'Tariq Yousef', 'email' => 'tariq@example.com', 'branch_id' => $riverside->id]);
        $riversideManager->assignRole('branch_manager');

        // --- Catalog: deterministic, one row per real item, no collision risk ---
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
                        : (str_contains($itemName, 'L') ? 'liters' : 'pcs'),
                ]));
            }
        }

        // --- Stock, via the real ledger — every starting quantity is a real movement ---
        $stockService = app(StockMovementService::class);
        $branches = [$downtown, $westside, $riverside];
        $faker = \Faker\Factory::create();

        foreach ($products as $product) {
            foreach ($branches as $branch) {
                if (random_int(1, 10) > 7) {
                    continue;
                }

                BranchStock::create([
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'quantity' => '0',
                    'sale_price' => (string) $faker->randomFloat(2, 2, 40),
                    'purchase_price' => (string) $faker->randomFloat(2, 1, 30),
                    'reorder_point' => (string) $faker->randomFloat(3, 5, 20),
                    'reorder_qty' => (string) $faker->randomFloat(3, 20, 60),
                ]);

                $stockService->submit(
                    $product,
                    $branch,
                    $admin,
                    new Number((string) random_int(30, 150)),
                    MovementType::InitialStockUpload,
                    note: 'Opening stock',
                );
            }
        }

        // --- Transfers: one of each lifecycle stage ---
        $transferService = app(TransferOrderService::class);
        $sharedProduct = BranchStock::where('branch_id', $downtown->id)->first()?->product;

        if ($sharedProduct) {
            BranchStock::firstOrCreate(
                ['branch_id' => $westside->id, 'product_id' => $sharedProduct->id],
                ['quantity' => '0', 'sale_price' => '10.00', 'purchase_price' => '7.00']
            );

            $transferService->createRequest($downtown, $westside, $downtownStaff, [
                ['product' => $sharedProduct, 'quantity' => new Number('5')],
            ]);

            $approved = $transferService->createRequest($downtown, $westside, $downtownStaff, [
                ['product' => $sharedProduct, 'quantity' => new Number('5')],
            ]);
            $transferService->approve($approved, $downtownManager);

            $inTransit = $transferService->createRequest($downtown, $westside, $downtownStaff, [
                ['product' => $sharedProduct, 'quantity' => new Number('5')],
            ]);
            $transferService->approve($inTransit, $downtownManager);
            $transferService->ship($inTransit, $downtownManager);

            $received = $transferService->createRequest($downtown, $westside, $downtownStaff, [
                ['product' => $sharedProduct, 'quantity' => new Number('10')],
            ]);
            $transferService->approve($received, $downtownManager);
            $transferService->ship($received, $downtownManager, [$sharedProduct->id => new Number('10')]);
            $transferService->receive($received, $westsideManager, [$sharedProduct->id => new Number('9')]);
        }
    }
}
