<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\TransferOrderService;
use BcMath\Number;
use Illuminate\Database\Seeder;

class TransferOrderSeeder extends Seeder
{
    public function run(): void
    {
        $transferService = app(TransferOrderService::class);

        $downtown = Branch::where('code', 'BR-DWT')->first();
        $westside = Branch::where('code', 'BR-WST')->first();
        $riverside = Branch::where('code', 'BR-RVS')->first();

        $staff = [
            $downtown->id => [
                'manager' => User::where('email', 'layla@example.com')->first(),
                'staff' => User::where('email', 'omar@example.com')->first(),
            ],
            $westside->id => [
                'manager' => User::where('email', 'rania@example.com')->first(),
                'staff' => User::where('email', 'yousef@example.com')->first(),
            ],
            $riverside->id => [
                'manager' => User::where('email', 'tariq@example.com')->first(),
                'staff' => User::where('email', 'hana@example.com')->first(),
            ],
        ];

        $this->seedPendingTransfers($transferService, $downtown, $westside, $riverside, $staff);
        $this->seedCompletedTransfers($transferService, $downtown, $westside, $riverside, $staff);
        $this->seedRejectedAndCancelled($transferService, $downtown, $westside, $riverside, $staff);
    }

    private function seedPendingTransfers(
        TransferOrderService $transferService,
        Branch $downtown,
        Branch $westside,
        Branch $riverside,
        array $staff,
    ): void {
        // 1) Requested (pending approval)
        $product = $this->sharedProduct($downtown, $westside);
        if ($product) {
            $transferService->createRequest($downtown, $westside, $staff[$downtown->id]['staff'], [
                ['product' => $product, 'quantity' => new Number('8')],
            ]);
        }

        // 2) Approved (not yet shipped)
        $product = $this->sharedProduct($westside, $riverside);
        if ($product) {
            $order = $transferService->createRequest($westside, $riverside, $staff[$westside->id]['staff'], [
                ['product' => $product, 'quantity' => new Number('6')],
            ]);
            $transferService->approve($order, $staff[$westside->id]['manager']);
        }

        // 3) In Transit
        $product = $this->sharedProduct($downtown, $riverside);
        if ($product) {
            $order = $transferService->createRequest($downtown, $riverside, $staff[$downtown->id]['staff'], [
                ['product' => $product, 'quantity' => new Number('10')],
            ]);
            $transferService->approve($order, $staff[$downtown->id]['manager']);
            $transferService->ship($order, $staff[$downtown->id]['staff']);

            $shipDate = now()->subDays(1);
            $order->update(['created_at' => $shipDate->copy()->subDays(2)]);
            StockMovement::where('transfer_order_id', $order->id)->update(['created_at' => $shipDate]);
        }
    }

    private function seedCompletedTransfers(
        TransferOrderService $transferService,
        Branch $downtown,
        Branch $westside,
        Branch $riverside,
        array $staff,
    ): void {
        $routes = [
            [$downtown, $westside],
            [$westside, $downtown],
            [$downtown, $riverside],
            [$riverside, $downtown],
            [$westside, $riverside],
        ];

        foreach ($routes as [$from, $to]) {
            $product = $this->sharedProduct($from, $to);
            if (! $product) {
                continue;
            }

            $qty = new Number((string) random_int(5, 15));
            $receivedQty = new Number((string) max(1, (int) $qty->sub(random_int(0, 2))->value));

            $fromStaff = $staff[$from->id]['staff'];
            $fromManager = $staff[$from->id]['manager'];
            $toManager = $staff[$to->id]['manager'];

            $order = $transferService->createRequest($from, $to, $fromStaff, [
                ['product' => $product, 'quantity' => $qty],
            ]);
            $transferService->approve($order, $fromManager);
            $transferService->ship($order, $fromStaff);
            $transferService->receive($order, $toManager, [$product->id => $receivedQty]);

            // Backdate with varied lead times so leadTimeDays() has real data.
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
    }

    private function seedRejectedAndCancelled(
        TransferOrderService $transferService,
        Branch $downtown,
        Branch $westside,
        Branch $riverside,
        array $staff,
    ): void {
        // Rejected
        $product = $this->sharedProduct($riverside, $downtown);
        if ($product) {
            $order = $transferService->createRequest($riverside, $downtown, $staff[$riverside->id]['staff'], [
                ['product' => $product, 'quantity' => new Number('4')],
            ]);
            $transferService->reject($order);
            $order->update(['created_at' => now()->subDays(8)]);
        }

        // Cancelled
        $product = $this->sharedProduct($downtown, $westside);
        if ($product) {
            $order = $transferService->createRequest($downtown, $westside, $staff[$downtown->id]['staff'], [
                ['product' => $product, 'quantity' => new Number('3')],
            ]);
            $transferService->cancel($order);
            $order->update(['created_at' => now()->subDays(5)]);
        }
    }

    private function sharedProduct(Branch $from, Branch $to): ?Product
    {
        return BranchStock::where('branch_id', $from->id)
            ->where('quantity', '>', 15)
            ->whereIn('product_id', BranchStock::where('branch_id', $to->id)->pluck('product_id'))
            ->inRandomOrder()
            ->first()?->product;
    }
}
