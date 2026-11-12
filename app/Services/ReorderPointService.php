<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use BcMath\Number;
use Carbon\Carbon;
use Illuminate\Support\Collection;
class ReorderPointService
{
    private const LOOKBACK_DAYS = 30;
    private const MIN_SALES_FOR_TRUST = 5;
    private const MIN_TRANSFERS_FOR_TRUST = 5;
    private const LEAD_TIME_FALLBACK_DAYS = 2;
    private const SAFETY_BUFFER_DAYS = 1;

    /**
     * Average units sold per day over the lookback window, or null when
     * there isn't enough sales history to trust the number — the caller
     * falls back to the static reorder_point in that case.
     */
    public function averageDailyUsage(Branch $branch, Product $product): ?Number
    {
        $saleMovements = StockMovement::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->where('type', MovementType::Sale)
            ->where('created_at', '>=', now()->subDays(self::LOOKBACK_DAYS));

        if ($saleMovements->count() < self::MIN_SALES_FOR_TRUST) {
            return null;
        }

        // Sale rows are always negative (outbound) — flip to magnitude
        // before averaging, since usage should read as a positive rate.
        $totalSold = new Number((string) $saleMovements->sum('quantity'));

        if ($totalSold->compare(0) < 0) {
            $totalSold = $totalSold->mul(-1);
        }

        return $totalSold->div(self::LOOKBACK_DAYS, 3);
    }

    /**
     * Average days between ship and receive for completed transfers on
     * this specific route, or null when there isn't enough history —
     * the caller falls back to LEAD_TIME_FALLBACK_DAYS in that case.
     */
    public function leadTimeDays(Branch $from, Branch $to): ?Number
    {
        // One query per movement type, each returning [transfer_order_id => earliest timestamp],
        // filtered to only this specific route's completed transfers.
        $shippedAt = StockMovement::query()
            ->where('type', MovementType::TransferOut)
            ->whereHas('transferOrder', fn ($q) => $q
                ->where('from_branch_id', $from->id)
                ->where('to_branch_id', $to->id)
                ->where('status', TransferStatus::Received))
            ->selectRaw('transfer_order_id, MIN(created_at) as shipped_at')
            ->groupBy('transfer_order_id')
            ->pluck('shipped_at', 'transfer_order_id');

        $receivedAt = StockMovement::query()
            ->where('type', MovementType::TransferIn)
            ->whereHas('transferOrder', fn ($q) => $q
                ->where('from_branch_id', $from->id)
                ->where('to_branch_id', $to->id)
                ->where('status', TransferStatus::Received))
            ->selectRaw('transfer_order_id, MIN(created_at) as received_at')
            ->groupBy('transfer_order_id')
            ->pluck('received_at', 'transfer_order_id');

        // Only transfers appearing in both sets actually completed the full journey.
        $durations = $shippedAt->keys()->intersect($receivedAt->keys())->map(
            fn ($id) => Carbon::parse($shippedAt[$id])->diffInDays(Carbon::parse($receivedAt[$id]))
        );

        if ($durations->count() < self::MIN_TRANSFERS_FOR_TRUST) {
            return null;
        }

        $total = $durations->reduce(
            fn (Number $carry, int $days) => $carry->add($days),
            new Number('0'),
        );

        return $total->div($durations->count(), 3);
    }

    public function reorderPoint(Branch $branch, Product $product): ?Number
    {
        $usage = $this->averageDailyUsage($branch, $product);

        if (! $usage) {
            // Not enough sales history to compute a real ROP — fall back
            // to whatever threshold a human configured when attaching
            // this product to this branch. This IS the reorder point,
            // not a rate to feed into the formula.
            $branchStock = BranchStock::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->first();

            return $branchStock?->reorder_point;
        }

        // Real ROP: (usage × lead time) + (usage × safety buffer)
        $leadTime = new Number((string) self::LEAD_TIME_FALLBACK_DAYS);
        $safetyBuffer = new Number((string) self::SAFETY_BUFFER_DAYS);

        return $usage->mul($leadTime, 3)->add($usage->mul($safetyBuffer, 3));
    }

    public function available(Branch $branch, Product $product): ?Number
    {
        $branchStock = BranchStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->first();

        if (! $branchStock) {
            return null;
        }

        return $branchStock->available();
    }

    public function suggestTransfer(Branch $branch, Product $product): ?array
    {
        $targetBranchStock = BranchStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->first();

        if (! $targetBranchStock) {
            return null;
        }

        $available = $targetBranchStock->available();
        $reorderPoint = $this->reorderPoint($branch, $product);

        if (! $reorderPoint || $available->compare($reorderPoint) >= 0) {
            return null;
        }

        $fallbackLeadTime = new Number((string) self::LEAD_TIME_FALLBACK_DAYS);

        $bestCandidate = BranchStock::query()
            ->with('branch')
            ->where('product_id', $product->id)
            ->where('branch_id', '!=', $branch->id)
            ->get()
            ->map(function (BranchStock $stock) use ($product, $branch, $fallbackLeadTime) {
                $candidateRop = $this->reorderPoint($stock->branch, $product) ?? new Number('0');
                $surplus = $stock->available()->sub($candidateRop);

                return [
                    'stock' => $stock,
                    'surplus' => $surplus,
                    'lead_time' => $this->leadTimeDays($stock->branch, $branch) ?? $fallbackLeadTime,
                ];
            })
            ->filter(fn (array $item) => $item['surplus']->compare(0) > 0)
            ->sort(function (array $a, array $b) {
                $leadComparison = $a['lead_time']->compare($b['lead_time']);

                return $leadComparison !== 0
                    ? $leadComparison
                    : $b['surplus']->compare($a['surplus']);
            })
            ->first();

        if (! $bestCandidate) {
            return null;
        }

        // Use the configured reorder_qty when available — sized to last
        // a reasonable stretch before the next trigger, not just enough
        // to touch the threshold and fire again tomorrow. Fall back to
        // the bare minimum (gap between available and ROP) when no
        // reorder_qty was configured.
        $reorderQty = $targetBranchStock->reorder_qty;
        $bareMinimum = $reorderPoint->sub($available);

        $needed = $reorderQty && $reorderQty->compare(0) > 0
            ? $reorderQty
            : $bareMinimum;

        // Never suggest more than the source can actually spare.
        $transferQuantity = $needed->compare($bestCandidate['surplus']) <= 0
            ? $needed
            : $bestCandidate['surplus'];

        return [
            'product' => $product,
            'to_branch' => $branch,
            'from_branch' => $bestCandidate['stock']->branch,
            'quantity' => $transferQuantity,
            'available' => $available,
            'reorder_point' => $reorderPoint,
        ];
    }

    public function suggestAllTransfers(): Collection
    {
        return BranchStock::with(['branch', 'product'])
            ->get()
            ->map(fn (BranchStock $stock) => $this->suggestTransfer($stock->branch, $stock->product))
            ->filter()
            ->values();
    }


}
