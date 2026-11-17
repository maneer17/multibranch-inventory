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

class ReorderPointService
{
    private const LOOKBACK_DAYS = 30;
    private const MIN_SALES_FOR_TRUST = 5;
    private const MIN_TRANSFERS_FOR_TRUST = 5;
    private const LEAD_TIME_FALLBACK_DAYS = 2;
    private const SAFETY_BUFFER_DAYS = 1;

    // ──────────────────────────────────────────────────────────
    //  Shared formula — single source of truth for the ROP math
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

    // ──────────────────────────────────────────────────────────
    //  Single-record methods — for detail pages, AI tool, etc.
    // ──────────────────────────────────────────────────────────

    /**
     * Average units sold per day over the last 30 days.
     *
     * Returns null when there aren't enough sales to trust the number,
     * so the caller can fall back to a manually configured threshold.
     */
    public function averageDailyUsage(Branch $branch, Product $product): ?Number
    {
        $result = StockMovement::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->where('type', MovementType::Sale)
            ->where('created_at', '>=', now()->subDays(self::LOOKBACK_DAYS))
            ->selectRaw('COUNT(*) as sale_count, COALESCE(SUM(quantity), 0) as total_qty')
            ->first();

        if ($result->sale_count < self::MIN_SALES_FOR_TRUST) {
            return null;
        }

        $totalSold = new Number((string) $result->total_qty);

        // Sales are stored as negative (outbound) — flip to positive.
        if ($totalSold->compare(0) < 0) {
            $totalSold = $totalSold->mul(-1);
        }

        return $totalSold->div(self::LOOKBACK_DAYS, 3);
    }

    /**
     * Average days a transfer takes on a specific route (from → to).
     *
     * Looks at completed transfers only. Returns null when there
     * aren't enough transfers to trust the number.
     */
    public function leadTimeDays(Branch $from, Branch $to): ?Number
    {
        $routeFilter = fn ($q) => $q
            ->where('from_branch_id', $from->id)
            ->where('to_branch_id', $to->id)
            ->where('status', TransferStatus::Received);

        $shippedAt = StockMovement::query()
            ->where('type', MovementType::TransferOut)
            ->whereHas('transferOrder', $routeFilter)
            ->selectRaw('transfer_order_id, MIN(created_at) as shipped_at')
            ->groupBy('transfer_order_id')
            ->pluck('shipped_at', 'transfer_order_id');

        $receivedAt = StockMovement::query()
            ->where('type', MovementType::TransferIn)
            ->whereHas('transferOrder', $routeFilter)
            ->selectRaw('transfer_order_id, MIN(created_at) as received_at')
            ->groupBy('transfer_order_id')
            ->pluck('received_at', 'transfer_order_id');

        $durations = $shippedAt->keys()
            ->intersect($receivedAt->keys())
            ->map(fn ($id) => Carbon::parse($shippedAt[$id])
                ->diffInDays(Carbon::parse($receivedAt[$id])));

        if ($durations->count() < self::MIN_TRANSFERS_FOR_TRUST) {
            return null;
        }

        $total = $durations->reduce(
            fn (Number $carry, int $days) => $carry->add($days),
            new Number('0'),
        );

        return $total->div($durations->count(), 3);
    }

    /**
     * The reorder point for one branch + product.
     *
     * If there's enough sales history → uses the formula.
     * If not → falls back to the manually configured threshold.
     */
    public function reorderPoint(Branch $branch, Product $product): ?Number
    {
        $usage = $this->averageDailyUsage($branch, $product);

        if (! $usage) {
            return BranchStock::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->value('reorder_point');
        }

        $leadTime = new Number((string) self::LEAD_TIME_FALLBACK_DAYS);

        return $this->computeRop($usage, $leadTime);
    }

    /**
     * Available stock for one branch + product (quantity minus reserved).
     */
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

    /**
     * Stock already on its way INTO this branch for this product.
     *
     * Counts transfers that have been requested, approved or shipped but
     * not yet received. Without this, a shortage keeps looking like a
     * shortage after someone has already created a transfer for it.
     */
    public function incomingQuantity(Branch $branch, Product $product): Number
    {
        $total = $this->incomingQuery()
            ->where('transfer_orders.to_branch_id', $branch->id)
            ->where('transfer_order_items.product_id', $product->id)
            ->selectRaw($this->incomingSumSql().' as total_incoming', [TransferStatus::InTransit->value])
            ->value('total_incoming');

        return new Number((string) ($total ?? '0'));
    }

    /**
     * Suggest a single transfer for one branch + product.
     *
     * Returns null if the inventory position (stock + incoming - reserved)
     * is at or above the reorder point, or no candidate branch has surplus
     * to share.
     */
    public function suggestTransfer(Branch $branch, Product $product): ?array
    {
        $targetStock = BranchStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->first();

        if (! $targetStock) {
            return null;
        }

        $available = $targetStock->available();
        $incoming = $this->incomingQuantity($branch, $product);
        $reorderPoint = $this->reorderPoint($branch, $product);

        // Inventory position = what is here (minus reserved) + what is on its way.
        // If that already covers the reorder point, no transfer is needed.
        if (! $reorderPoint || $available->add($incoming)->compare($reorderPoint) >= 0) {
            return null;
        }

        $fallbackLeadTime = new Number((string) self::LEAD_TIME_FALLBACK_DAYS);

        // Find the best source: closest (by lead time), with most surplus.
        $bestCandidate = BranchStock::query()
            ->with('branch')
            ->where('product_id', $product->id)
            ->where('branch_id', '!=', $branch->id)
            ->get()
            ->map(function (BranchStock $stock) use ($product, $branch, $fallbackLeadTime) {
                $candidateRop = $this->reorderPoint($stock->branch, $product) ?? new Number('0');

                return [
                    'stock'     => $stock,
                    'surplus'   => $stock->available()->sub($candidateRop),
                    'lead_time' => $this->leadTimeDays($stock->branch, $branch) ?? $fallbackLeadTime,
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

        if (! $bestCandidate) {
            return null;
        }

        return $this->buildSuggestion(
            $product, $branch, $bestCandidate,
            $available, $incoming, $reorderPoint, $targetStock,
        );
    }

    // ──────────────────────────────────────────────────────────
    //  Bulk path — for the dashboard / scheduled checks
    //  A fixed number of queries, however many branches/products.
    // ──────────────────────────────────────────────────────────

    /**
     * Suggest transfers for every branch + product that is
     * below its reorder point.
     */
    public function suggestAllTransfers(): Collection
    {
        // ① Load everything up front (a fixed number of queries).
        $allStock    = BranchStock::with(['branch', 'product'])->get();
        $usageMap    = $this->bulkUsageStats();
        $reservedMap = $this->bulkReservedQuantities();
        $incomingMap = $this->bulkIncomingQuantities();
        $leadTimeMap = $this->bulkLeadTimes();

        // ② Pre-compute ROP, available and inventory position for every (branch, product).
        //    available = on hand - reserved to go out   (what a branch can SHARE)
        //    position  = available + incoming           (what a branch HAS COMING, for its own shortage check)
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

            $usage = $usageMap[$key] ?? null;
            $ropMap[$key] = $usage
                ? $this->computeRop($usage, $leadTime)
                : $stock->reorder_point;
        }

        // ③ Group by product so we can find candidates without querying.
        $byProduct       = $allStock->groupBy('product_id');
        $fallbackLeadTime = $leadTime;

        // ④ Walk every row — pure map lookups, zero queries.
        return $allStock
            ->map(function (BranchStock $stock) use ($ropMap, $availableMap, $positionMap, $incomingMap, $byProduct, $leadTimeMap, $fallbackLeadTime) {

                $key          = $this->stockKey($stock);
                $available    = $availableMap[$key];
                $incoming     = $incomingMap[$key] ?? new Number('0');
                $reorderPoint = $ropMap[$key] ?? null;

                // Stock plus what is already on its way covers the reorder point — skip.
                if (! $reorderPoint || $positionMap[$key]->compare($reorderPoint) >= 0) {
                    return null;
                }

                // Candidates = other branches that carry this product.
                $candidates = ($byProduct[$stock->product_id] ?? collect())
                    ->filter(fn (BranchStock $s) => $s->branch_id !== $stock->branch_id);

                $bestCandidate = $candidates
                    ->map(function (BranchStock $candidate) use ($stock, $ropMap, $availableMap, $leadTimeMap, $fallbackLeadTime) {
                        $cKey = $this->stockKey($candidate);

                        $candidateRop       = $ropMap[$cKey] ?? new Number('0');
                        $candidateAvailable = $availableMap[$cKey] ?? new Number('0');

                        $routeKey = "{$candidate->branch_id}:{$stock->branch_id}";

                        return [
                            'stock'     => $candidate,
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
    //  Private helpers
    // ──────────────────────────────────────────────────────────

    /**
     * Consistent map key for a BranchStock row.
     */
    private function stockKey(BranchStock $stock): string
    {
        return "{$stock->branch_id}:{$stock->product_id}";
    }

    /**
     * Build the suggestion array — shared by both paths so the
     * output shape stays consistent.
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
            'quantity'       => $quantity,
            'available'      => $available,
            'incoming'       => $incoming,
            'reorder_point'  => $reorderPoint,
        ];
    }

    // ──────────────────────────────────────────────────────────
    //  Incoming-stock query helpers (shared by single + bulk paths)
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
    //  Bulk data loaders (one query each)
    // ──────────────────────────────────────────────────────────

    /**
     * All usage rates in one query, keyed "branch_id:product_id".
     */
    private function bulkUsageStats(): array
    {
        $rows = StockMovement::query()
            ->where('type', MovementType::Sale)
            ->where('created_at', '>=', now()->subDays(self::LOOKBACK_DAYS))
            ->selectRaw('branch_id, product_id, COUNT(*) as sale_count, ABS(SUM(quantity)) as total_sold')
            ->groupBy('branch_id', 'product_id')
            ->get();

        $map     = [];
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
     * All reserved (pending outbound) quantities in one query,
     * keyed "branch_id:product_id".
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
     * All incoming quantities in one query, keyed "to_branch_id:product_id".
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
     * All route lead times in two queries (shipped + received),
     * keyed "from_branch_id:to_branch_id".
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

        // Pair shipped ↔ received, group durations by route.
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
