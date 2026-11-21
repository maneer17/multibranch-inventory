<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\TransferOrderItem;
use BcMath\Number;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Works out which branches are running low and where to move stock from.
 *
 * Everything is calculated in bulk: the data is loaded up front in a fixed
 * number of queries, then each branch and product is checked with plain
 * array lookups. Adding more branches or products does not add queries.
 */
class ReorderPointService
{
    private const LOOKBACK_DAYS = 30;
    private const MIN_SALES_FOR_TRUST = 5;
    private const MIN_TRANSFERS_FOR_TRUST = 5;
    private const LEAD_TIME_FALLBACK_DAYS = 2;
    private const SAFETY_BUFFER_DAYS = 1;

    // ──────────────────────────────────────────────────────────
    //  Main entry point
    // ──────────────────────────────────────────────────────────

    /**
     * Suggest a transfer for every branch + product that is below its
     * reorder point and has another branch able to help.
     *
     * Each suggestion is an array with: product, to_branch, from_branch,
     * quantity, available, incoming, reorder_point.
     */
    public function suggestAllTransfers(): Collection
    {
        // ① Load everything up front (a fixed number of queries).
        //    We load every branch's stock, not just the current user's,
        //    because a branch's best source is usually a different branch.
        $allStock    = BranchStock::with(['branch:id,name,code', 'product:id,name,sku'])->get();
        $usageMap    = $this->bulkUsageStats();
        $reservedMap = $this->bulkReservedQuantities();
        $incomingMap = $this->bulkIncomingQuantities();
        $leadTimeMap = $this->bulkLeadTimes();

        // ② Pre-compute three numbers for every (branch, product):
        //    available = on hand - reserved to go out   (what a branch can SHARE)
        //    position  = available + incoming           (what a branch HAS, counting what is on its way)
        //    rop       = the level below which it needs more stock
        $ropMap       = [];
        $availableMap = [];
        $positionMap  = [];
        $leadTime     = new Number((string) self::LEAD_TIME_FALLBACK_DAYS);

        foreach ($allStock as $stock) {
            $key      = $this->stockKey($stock);
            $reserved = $reservedMap[$key] ?? new Number('0');
            $incoming = $incomingMap[$key] ?? new Number('0');

            $availableMap[$key] = $stock->quantity->sub($reserved);
            $positionMap[$key]  = $availableMap[$key]->add($incoming);

            // Enough sales history: use the formula. Otherwise fall back
            // to the reorder point someone set by hand.
            $usage = $usageMap[$key] ?? null;
            $ropMap[$key] = $usage
                ? $this->computeRop($usage, $leadTime)
                : $stock->reorder_point;
        }

        // ③ Group by product so we can find candidate sources without querying.
        $byProduct        = $allStock->groupBy('product_id');
        $fallbackLeadTime = $leadTime;

        // ④ Check every row using only map lookups (zero queries).
        return $allStock
            ->map(function (BranchStock $stock) use ($ropMap, $availableMap, $positionMap, $incomingMap, $byProduct, $leadTimeMap, $fallbackLeadTime) {

                $key          = $this->stockKey($stock);
                $available    = $availableMap[$key];
                $incoming     = $incomingMap[$key] ?? new Number('0');
                $reorderPoint = $ropMap[$key] ?? null;

                // No reorder point, or stock plus what is already on its way
                // covers it: nothing to suggest.
                if (! $reorderPoint || $positionMap[$key]->compare($reorderPoint) >= 0) {
                    return null;
                }

                // Candidate sources = other branches that carry this product.
                $candidates = ($byProduct[$stock->product_id] ?? collect())
                    ->filter(fn (BranchStock $s) => $s->branch_id !== $stock->branch_id);

                // Best source = shortest lead time, then the most surplus.
                $bestCandidate = $candidates
                    ->map(function (BranchStock $candidate) use ($stock, $ropMap, $availableMap, $leadTimeMap, $fallbackLeadTime) {
                        $cKey = $this->stockKey($candidate);

                        $candidateRop       = $ropMap[$cKey] ?? new Number('0');
                        $candidateAvailable = $availableMap[$cKey] ?? new Number('0');

                        $routeKey = "{$candidate->branch_id}:{$stock->branch_id}";

                        return [
                            'stock'     => $candidate,
                            // Surplus = what the source can spare while staying above its own reorder point.
                            'surplus'   => $candidateAvailable->sub($candidateRop),
                            'lead_time' => $leadTimeMap[$routeKey] ?? $fallbackLeadTime,
                        ];
                    })
                    ->filter(fn (array $c) => $c['surplus']->compare(0) > 0)
                    ->sort(function (array $a, array $b) {
                        $byLeadTime = $a['lead_time']->compare($b['lead_time']);

                        return $byLeadTime !== 0
                            ? $byLeadTime
                            : $b['surplus']->compare($a['surplus']);
                    })
                    ->first();

                // Nobody has stock to spare.
                if (! $bestCandidate) {
                    return null;
                }

                return $this->buildSuggestion(
                    $stock->product, $stock->branch, $bestCandidate,
                    $available, $incoming, $reorderPoint, $stock,
                );
            })
            ->filter()
            ->values();
    }

    // ──────────────────────────────────────────────────────────
    //  Calculation helpers
    // ──────────────────────────────────────────────────────────

    /**
     * ROP = (daily usage × lead time) + (daily usage × safety buffer)
     */
    private function computeRop(Number $usage, Number $leadTime): Number
    {
        $safetyBuffer = new Number((string) self::SAFETY_BUFFER_DAYS);

        return $usage
            ->mul($leadTime, 3)
            ->add($usage->mul($safetyBuffer, 3));
    }

    /**
     * Consistent map key for a BranchStock row.
     */
    private function stockKey(BranchStock $stock): string
    {
        return "{$stock->branch_id}:{$stock->product_id}";
    }

    /**
     * Build one suggestion array.
     */
    private function buildSuggestion(
        Product $product,
        Branch $toBranch,
        array $bestCandidate,
        Number $available,
        Number $incoming,
        Number $reorderPoint,
        BranchStock $targetStock,
    ): array {
        // The gap is measured against the inventory position, so stock
        // already on its way in is not ordered a second time.
        $bareMinimum = $reorderPoint->sub($available->add($incoming));
        $reorderQty  = $targetStock->reorder_qty;

        // Use the configured reorder quantity if there is one,
        // otherwise just cover the gap.
        $needed = $reorderQty && $reorderQty->compare(0) > 0
            ? $reorderQty
            : $bareMinimum;

        // Never suggest more than the source can spare.
        $quantity = $needed->compare($bestCandidate['surplus']) <= 0
            ? $needed
            : $bestCandidate['surplus'];

        return [
            'product'       => $product,
            'to_branch'     => $toBranch,
            'from_branch'   => $bestCandidate['stock']->branch,
            'quantity'      => $quantity,
            'available'     => $available,
            'incoming'      => $incoming,
            'reorder_point' => $reorderPoint,
        ];
    }

    // ──────────────────────────────────────────────────────────
    //  Incoming-stock query helpers
    // ──────────────────────────────────────────────────────────

    /**
     * Transfer items that have not arrived yet: requested, approved or in transit.
     */
    private function incomingQuery()
    {
        return TransferOrderItem::query()
            ->join('transfer_orders', 'transfer_orders.id', '=', 'transfer_order_items.transfer_order_id')
            ->whereIn('transfer_orders.status', [
                TransferStatus::Requested->value,
                TransferStatus::Approved->value,
                TransferStatus::InTransit->value,
            ]);
    }

    /**
     * Expected incoming quantity per item. Once shipped, the amount actually
     * sent is what will arrive; before that, it is the amount requested.
     * Needs one binding: the in-transit status value.
     */
    private function incomingSumSql(): string
    {
        return 'SUM(CASE WHEN transfer_orders.status = ? '
            .'THEN COALESCE(transfer_order_items.quantity_sent, transfer_order_items.quantity_requested) '
            .'ELSE transfer_order_items.quantity_requested END)';
    }

    // ──────────────────────────────────────────────────────────
    //  Bulk data loaders (one query each, except lead times)
    // ──────────────────────────────────────────────────────────

    /**
     * Average daily sales per branch + product over the last 30 days,
     * keyed "branch_id:product_id".
     *
     * Pairs with fewer than 5 sales are left out, so the caller falls back
     * to the manually configured reorder point.
     */
    private function bulkUsageStats(): array
    {
        $rows = StockMovement::query()
            ->where('type', MovementType::Sale)
            ->where('created_at', '>=', now()->subDays(self::LOOKBACK_DAYS))
            ->selectRaw('branch_id, product_id, COUNT(*) as sale_count, ABS(SUM(quantity)) as total_sold')
            ->groupBy('branch_id', 'product_id')
            ->get();

        $map      = [];
        $lookback = new Number((string) self::LOOKBACK_DAYS);

        foreach ($rows as $row) {
            if ($row->sale_count < self::MIN_SALES_FOR_TRUST) {
                continue;
            }

            $key       = "{$row->branch_id}:{$row->product_id}";
            $map[$key] = (new Number((string) $row->total_sold))->div($lookback, 3);
        }

        return $map;
    }

    /**
     * Reserved (pending outbound) quantities, keyed "branch_id:product_id".
     *
     * "Reserved" = quantity_requested on transfer orders that are
     * still Requested or Approved (not yet shipped).
     */
    private function bulkReservedQuantities(): array
    {
        $rows = TransferOrderItem::query()
            ->join('transfer_orders', 'transfer_orders.id', '=', 'transfer_order_items.transfer_order_id')
            ->whereIn('transfer_orders.status', [
                TransferStatus::Requested->value,
                TransferStatus::Approved->value,
            ])
            ->selectRaw('transfer_orders.from_branch_id as branch_id, transfer_order_items.product_id, SUM(transfer_order_items.quantity_requested) as total_reserved')
            ->groupBy('branch_id', 'transfer_order_items.product_id')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $key       = "{$row->branch_id}:{$row->product_id}";
            $map[$key] = new Number((string) $row->total_reserved);
        }

        return $map;
    }

    /**
     * Incoming quantities, keyed "to_branch_id:product_id".
     *
     * "Incoming" = stock on transfers headed to a branch that have been
     * requested, approved or shipped, but not yet received.
     */
    private function bulkIncomingQuantities(): array
    {
        $rows = $this->incomingQuery()
            ->selectRaw(
                'transfer_orders.to_branch_id as branch_id, transfer_order_items.product_id, '.$this->incomingSumSql().' as total_incoming',
                [TransferStatus::InTransit->value],
            )
            ->groupBy('transfer_orders.to_branch_id', 'transfer_order_items.product_id')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $key       = "{$row->branch_id}:{$row->product_id}";
            $map[$key] = new Number((string) $row->total_incoming);
        }

        return $map;
    }

    /**
     * Average transfer time per route, keyed "from_branch_id:to_branch_id".
     * Uses two queries (shipped times + received times).
     *
     * Only completed transfers count, and routes with fewer than 5 of them
     * are left out, so the caller falls back to the default lead time.
     */
    private function bulkLeadTimes(): array
    {
        $shippedRows = StockMovement::query()
            ->where('type', MovementType::TransferOut)
            ->join('transfer_orders', 'transfer_orders.id', '=', 'stock_movements.transfer_order_id')
            ->where('transfer_orders.status', TransferStatus::Received->value)
            ->selectRaw('transfer_orders.from_branch_id, transfer_orders.to_branch_id, stock_movements.transfer_order_id, MIN(stock_movements.created_at) as shipped_at')
            ->groupBy('transfer_orders.from_branch_id', 'transfer_orders.to_branch_id', 'stock_movements.transfer_order_id')
            ->get();

        $receivedRows = StockMovement::query()
            ->where('type', MovementType::TransferIn)
            ->join('transfer_orders', 'transfer_orders.id', '=', 'stock_movements.transfer_order_id')
            ->where('transfer_orders.status', TransferStatus::Received->value)
            ->selectRaw('transfer_orders.from_branch_id, transfer_orders.to_branch_id, stock_movements.transfer_order_id, MIN(stock_movements.created_at) as received_at')
            ->groupBy('transfer_orders.from_branch_id', 'transfer_orders.to_branch_id', 'stock_movements.transfer_order_id')
            ->get();

        // Index received rows for quick lookup.
        $receivedByOrder = $receivedRows->keyBy('transfer_order_id');

        // Pair each shipped row with its received row, grouped by route.
        $routeDurations = [];

        foreach ($shippedRows as $row) {
            $received = $receivedByOrder[$row->transfer_order_id] ?? null;

            if (! $received) {
                continue;
            }

            $routeKey = "{$row->from_branch_id}:{$row->to_branch_id}";
            $routeDurations[$routeKey][] = Carbon::parse($row->shipped_at)
                ->diffInDays(Carbon::parse($received->received_at));
        }

        // Average each route's durations.
        $map = [];

        foreach ($routeDurations as $routeKey => $durations) {
            if (count($durations) < self::MIN_TRANSFERS_FOR_TRUST) {
                continue;
            }

            $total = array_reduce(
                $durations,
                fn (Number $carry, int $d) => $carry->add($d),
                new Number('0'),
            );

            $map[$routeKey] = $total->div(count($durations), 3);
        }

        return $map;
    }
}
