<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use BcMath\Number;
use App\Models\TransferOrder;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    private const SCALE = 3;

    public function submit(
        Product $product,
        Branch $branch,
        User $user,
        Number $quantity,
        MovementType $type,
        ?string $note = null,
        ?TransferOrder $transferOrder = null,
    ): StockMovement {
        if ($quantity->compare(0) <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0.');
        }

        $exists = $branch->products()
            ->where('product_id', $product->id)
            ->exists();

        if (! $exists) {
            throw new \DomainException('No branch_stock row exists for this product at this branch.');
        }

        // Normalize to the column's scale here, once, so every caller's
        // input ends up consistent regardless of how many decimals they
        // happened to pass — the service owns this, not the caller.
        $quantity = $quantity->round(self::SCALE);
        $signedQuantity = $type->isOutbound() ? $quantity->mul(-1) : $quantity;

        return DB::transaction(function () use (
            $product, $branch, $user, $signedQuantity, $type, $note, $transferOrder
        ) {
            $branchStock = BranchStock::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if ($branchStock->quantity->add($signedQuantity) < 0) {
                throw new \DomainException('This movement would take stock below zero.');
            }

            $movement = StockMovement::create([
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'quantity' => $signedQuantity,
                'type' => $type,
                'note' => $note,
                'created_by' => $user->id,
                'transfer_order_id' => $transferOrder?->id,
            ]);

            $branchStock->update([
                'quantity' => $branchStock->quantity->add($signedQuantity),
            ]);

            return $movement;
        });
    }
}
