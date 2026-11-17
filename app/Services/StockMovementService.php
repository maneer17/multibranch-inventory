<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use App\Models\User;
use BcMath\Number;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    public const SCALE = 3;

    public function submit(
        Product $product,
        Branch $branch,
        User $user,
        Number $quantity,
        MovementType $type,
        ?string $note = null,
        ?TransferOrder $transferOrder = null,
        ?Number $unitPrice = null,
    ): StockMovement {
        // Round first, then validate: 0.0004 rounds to 0.000 and must be rejected.
        $quantity = $quantity->round(self::SCALE);

        if ($quantity->compare(0) <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0.');
        }

        // Transfer movements must belong to a transfer order, and only they may.
        if ($type->isTransfer() !== ($transferOrder !== null)) {
            throw new \InvalidArgumentException(
                'Transfer movements require a transfer order, and other movements must not have one.'
            );
        }

        $signedQuantity = $type->isOutbound() ? $quantity->mul(-1) : $quantity;

        return DB::transaction(function () use (
            $product, $branch, $user, $signedQuantity, $type, $note, $transferOrder, $unitPrice
        ) {
            $branchStock = BranchStock::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first()
                ?? throw new \DomainException('No branch_stock row exists for this product at this branch.');

            $newQuantity = $branchStock->quantity->add($signedQuantity);

            if ($newQuantity->compare(0) < 0) {
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
                'unit_price' => $unitPrice,
            ]);

            $branchStock->update(['quantity' => $newQuantity]);

            return $movement;
        });
    }
}
