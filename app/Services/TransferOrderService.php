<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\TransferOrder;
use App\Models\TransferOrderItem;
use App\Models\User;
use BcMath\Number;
use Illuminate\Support\Facades\DB;

class TransferOrderService
{
    public function __construct(private StockMovementService $stockMovementService){
    }
    private const SCALE = 3;

    /**
     * @param  array<int, array{product: \App\Models\Product, quantity: Number}>  $items
     */
    public function createRequest(Branch $from, Branch $to, User $requester, array $items): TransferOrder
    {
        if ($from->is($to)) {
            throw new \InvalidArgumentException('A transfer needs two different branches.');
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('A transfer needs at least one item.');
        }

        $lines = [];
        $names = [];

        foreach ($items as $item) {
            $product = $item['product'];
            $quantity = $item['quantity']->round(self::SCALE);

            if ($quantity->compare(0) <= 0) {
                throw new \InvalidArgumentException('Quantity must be greater than 0.');
            }

            if (isset($names[$product->id])) {
                throw new \InvalidArgumentException('The same product appears twice in one transfer.');
            }

            $names[$product->id] = $product->name;
            $lines[] = ['product_id' => $product->id, 'quantity' => $quantity];
        }

        $productIds = array_keys($names);

        // Both branches must already carry every requested product —
        // not contention-sensitive, so this stays outside the lock.
        foreach ([$from, $to] as $branch) {
            $carried = BranchStock::where('branch_id', $branch->id)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->all();

            $missing = array_diff($productIds, $carried);

            if ($missing !== []) {
                $list = implode(', ', array_map(fn ($id) => $names[$id], $missing));
                throw new \DomainException("{$branch->name} does not carry: {$list}.");
            }
        }

        return DB::transaction(function () use ($from, $to, $requester, $lines, $names) {
            foreach ($lines as $line) {
                // Lock the source row: this is the mutex stopping two
                // concurrent requests from both passing the availability
                // check against the same stock.
                $sourceStock = BranchStock::where('branch_id', $from->id)
                    ->where('product_id', $line['product_id'])
                    ->lockForUpdate()
                    ->first();

                $reserved = $this->reservedQuantity($from->id, $line['product_id']);
                $available = $sourceStock->quantity->sub($reserved);

                // Reject only when requested is strictly MORE than
                // available — requesting exactly what's available is fine.
                if ($line['quantity']->compare($available) > 0) {
                    $name = $names[$line['product_id']];
                    throw new \DomainException("{$from->name} does not have enough {$name} available.");
                }
            }

            $order = TransferOrder::create([
                'from_branch_id' => $from->id,
                'to_branch_id' => $to->id,
                'requested_by' => $requester->id,
                'status' => TransferStatus::Requested,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'quantity_requested' => $line['quantity'],
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * Sum of quantity_requested across this product's open transfers
     * (requested or approved) out of the given branch — computed fresh,
     * never stored, so it can't drift from the transfer rows themselves.
     */
    private function reservedQuantity(int $branchId, int $productId): Number
    {
        $sum = TransferOrderItem::query()
            ->where('product_id', $productId)
            ->whereHas('transferOrder', function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                    ->whereIn('status', [
                        TransferStatus::Requested->value,
                        TransferStatus::Approved->value,
                    ]);
            })
            ->get()
            ->reduce(
                fn (Number $carry, TransferOrderItem $item) => $carry->add($item->quantity_requested),
                new Number('0')
            );

        return $sum;
    }
    public  function cancel(TransferOrder $order): TransferOrder{
        return $this->changeOrderStatus($order,TransferStatus::Cancelled);
    }

    public function approve(TransferOrder $order,User $approver): TransferOrder
    {
        return $this->changeOrderStatus($order,TransferStatus::Approved, $approver);
    }

    public function reject (TransferOrder $order): TransferOrder
    {
        return $this->changeOrderStatus($order,TransferStatus::Rejected);
    }

    private function changeOrderStatus(TransferOrder $order,TransferStatus $status , ?User $approver=null): TransferOrder{
        if(! $order->status->canTransitionTo($status)){
            throw new \DomainException("you can't make this order status {$status->value}");
        }
        $order->update([
            'status' => $status,
            'approved_by' => $approver?->id,
        ]);
        return $order;
    }

    public function ship(TransferOrder $order, User $user, array $sentQuantities = []): TransferOrder
    {
        if (! $order->status->canTransitionTo(TransferStatus::InTransit)) {
            throw new \DomainException('This order cannot be shipped from its current status.');
        }

        return DB::transaction(function () use ($order, $user, $sentQuantities) {
            $order->update(['status' => TransferStatus::InTransit]);

            foreach ($order->items as $item) {
                // Defaults to what was requested — override only needed when
                // what's actually shipped differs (short-shipped, out of stock).
                $sentQty = $sentQuantities[$item->product_id] ?? $item->quantity_requested;

                $this->stockMovementService->submit(
                    $item->product,
                    $order->fromBranch,
                    $user,
                    $sentQty,
                    MovementType::TransferOut,
                    transferOrder: $order,
                );

                $item->update(['quantity_sent' => $sentQty]);
            }

            return $order;
        });
    }

    public function receive(TransferOrder $order, User $user, array $receivedQuantities = []): TransferOrder
    {
        if (! $order->status->canTransitionTo(TransferStatus::Received)) {
            throw new \DomainException('This order cannot be received from its current status.');
        }

        return DB::transaction(function () use ($order, $user, $receivedQuantities) {
            foreach ($order->items as $item) {
                // Defaults to what was sent — override only needed when what
                // actually arrived differs (damage, loss in transit).
                $receivedQty = ($receivedQuantities[$item->product_id] ?? $item->quantity_sent)->round(self::SCALE);

                $this->stockMovementService->submit(
                    $item->product,
                    $order->toBranch,
                    $user,
                    $receivedQty,
                    MovementType::TransferIn,
                    transferOrder: $order,
                );

                $item->update(['quantity_received' => $receivedQty]);
            }

            $order->update(['status' => TransferStatus::Received]);

            return $order;
        });
    }

}
