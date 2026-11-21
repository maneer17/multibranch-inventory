<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\TransferOrder;
use App\Models\User;
use BcMath\Number;
use Illuminate\Support\Facades\DB;

class TransferOrderService
{
    public function __construct(private StockMovementService $stockMovementService)
    {
    }

    /**
     * Create a transfer request (status: Requested).
     *
     * Stock on hand does not change here. The requested units simply count
     * as "reserved" at the source branch until the order is shipped,
     * cancelled, or rejected.
     *
     * @param  array<int, array{product: \App\Models\Product, quantity: Number}>  $items
     */
    public function createRequest(Branch $from, Branch $to, User $requester, array $items): TransferOrder
    {
        // Step 1: validate the input. This is plain PHP, so no database
        // work and no locks are needed yet.
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
            $quantity = $item['quantity']->round(StockMovementService::SCALE);

            if ($quantity->compare(0) <= 0) {
                throw new \InvalidArgumentException('Quantity must be greater than 0.');
            }

            if (isset($names[$product->id])) {
                throw new \InvalidArgumentException('The same product appears twice in one transfer.');
            }

            // $names is used for error messages and the duplicate check.
            $names[$product->id] = $product->name;
            $lines[] = ['product_id' => $product->id, 'quantity' => $quantity];
        }

        $productIds = array_keys($names);

        // Step 2: both branches must already carry every requested product.
        // These are read-only queries, so they stay outside the lock.
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

        // Lock rows in a fixed order (by product ID). Without this, two
        // requests that list the same products in a different order could
        // each hold one row while waiting for the other: a deadlock.
        $lines = collect($lines)->sortBy('product_id')->values()->all();

        // Step 3 and 4 run in one transaction: either the whole order is
        // saved or nothing is.
        return DB::transaction(function () use ($from, $to, $requester, $lines, $names) {
            // Step 3: lock each source stock row and check availability.
            foreach ($lines as $line) {
                // The lock makes concurrent requests take turns, so two of
                // them can't both pass the check against the same stock.
                $sourceStock = BranchStock::where('branch_id', $from->id)
                    ->where('product_id', $line['product_id'])
                    ->lockForUpdate()
                    ->first();

                // available = on hand minus units reserved by open orders.
                $available = $sourceStock->available();

                // Reject only when the request is MORE than what's available.
                // Asking for exactly what's available is fine.
                if ($line['quantity']->compare($available) > 0) {
                    $name = $names[$line['product_id']];
                    throw new \DomainException("{$from->name} does not have enough {$name} available.");
                }
            }

            // Step 4: every line passed, so save the order and its items.
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

    public function cancel(TransferOrder $order): TransferOrder
    {
        return $this->changeOrderStatus($order, TransferStatus::Cancelled);
    }

    public function approve(TransferOrder $order, User $approver): TransferOrder
    {
        return $this->changeOrderStatus($order, TransferStatus::Approved, $approver);
    }

    public function reject(TransferOrder $order): TransferOrder
    {
        return $this->changeOrderStatus($order, TransferStatus::Rejected);
    }

    /**
     * Move an order to a new status if the status rules allow it.
     * Used for cancel, approve, and reject.
     */
    private function changeOrderStatus(TransferOrder $order, TransferStatus $status, ?User $approver = null): TransferOrder
    {
        return DB::transaction(function () use ($order, $status, $approver) {
            // Lock the order row and re-read it from the database. The $order
            // we were given may be stale: someone else could have changed
            // its status a moment ago.
            $locked = TransferOrder::lockForUpdate()->findOrFail($order->id);

            if (! $locked->status->canTransitionTo($status)) {
                throw new \DomainException("This order cannot transition to {$status->value}.");
            }

            $data = ['status' => $status];

            if ($approver) {
                $data['approved_by'] = $approver->id;
            }

            $locked->update($data);

            // Refresh the caller's copy so it shows the new status.
            return $order->refresh();
        });
    }

    /**
     * Ship an order: take the sent quantities out of the source branch.
     *
     * @param  array<int, Number>  $sentQuantities  product_id => quantity actually sent
     */
    public function ship(TransferOrder $order, User $user, array $sentQuantities = []): TransferOrder
    {
        return DB::transaction(function () use ($order, $user, $sentQuantities) {
            // Lock and re-read the order so two simultaneous "ship" clicks
            // can't both pass the status check. Items are loaded sorted by
            // product ID so stock rows are locked in a fixed order
            // (same deadlock protection as createRequest).
            $order = TransferOrder::lockForUpdate()
                ->with([
                    'items' => fn ($query) => $query->orderBy('product_id'),
                    'items.product',
                    'fromBranch',
                ])
                ->findOrFail($order->id);

            if (! $order->status->canTransitionTo(TransferStatus::InTransit)) {
                throw new \DomainException('This order cannot be shipped from its current status.');
            }

            // Change the status first. An InTransit order no longer counts
            // as reserved, so its own units don't block the stock check below.
            $order->update(['status' => TransferStatus::InTransit]);

            foreach ($order->items as $item) {
                // Defaults to what was requested. Pass a different number
                // when less was actually shipped (short-shipped).
                $sentQty = ($sentQuantities[$item->product_id] ?? $item->quantity_requested)
                    ->round(StockMovementService::SCALE);

                // Never ship more than was requested.
                if ($sentQty->compare($item->quantity_requested) > 0) {
                    throw new \DomainException('Cannot ship more than was requested.');
                }

                // Writes the TransferOut movement and lowers the source stock.
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

    /**
     * Receive an order: add the received quantities to the destination branch.
     *
     * @param  array<int, Number>  $receivedQuantities  product_id => quantity actually received
     */
    public function receive(TransferOrder $order, User $user, array $receivedQuantities = []): TransferOrder
    {
        return DB::transaction(function () use ($order, $user, $receivedQuantities) {
            // Same pattern as ship(): lock, re-read, and sort items by
            // product ID before touching any stock rows.
            $order = TransferOrder::lockForUpdate()
                ->with([
                    'items' => fn ($query) => $query->orderBy('product_id'),
                    'items.product',
                    'toBranch',
                ])
                ->findOrFail($order->id);

            if (! $order->status->canTransitionTo(TransferStatus::Received)) {
                throw new \DomainException('This order cannot be received from its current status.');
            }

            foreach ($order->items as $item) {
                // Defaults to what was sent. Pass a different number when
                // less arrived (damage, loss in transit).
                $receivedQty = ($receivedQuantities[$item->product_id] ?? $item->quantity_sent)
                    ->round(StockMovementService::SCALE);

                // Nothing can arrive that was never sent.
                if ($receivedQty->compare($item->quantity_sent) > 0) {
                    throw new \DomainException('Cannot receive more than was sent.');
                }

                // Writes the TransferIn movement and raises the destination stock.
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

            // Mark as received only after every item has been added.
            $order->update(['status' => TransferStatus::Received]);

            return $order;
        });
    }
}
