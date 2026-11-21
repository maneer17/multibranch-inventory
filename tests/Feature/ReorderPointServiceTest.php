<?php

use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use App\Models\User;
use App\Services\ReorderPointService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ReorderPointService();
    $this->user = User::factory()->create();

    // The suggestion for one branch + product, or null if there is none.
    $this->suggestionFor = fn (Branch $branch, Product $product) => $this->service
        ->suggestAllTransfers()
        ->first(fn (array $s) => $s['to_branch']->id === $branch->id
            && $s['product']->id === $product->id);

    // Shortcut for creating a stock row.
    $this->stockAt = fn (Branch $branch, Product $product, string $quantity, string $reorderPoint, string $reorderQty = '0') => BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'reorder_point' => $reorderPoint,
        'reorder_qty' => $reorderQty,
    ]);

    // Record several sales of the same size at a branch.
    $this->sales = fn (Branch $branch, Product $product, int $count, string $quantity, int $daysAgo) => StockMovement::factory()
        ->count($count)
        ->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => MovementType::Sale,
            'quantity' => $quantity,
            'created_by' => $this->user->id,
            'created_at' => now()->subDays($daysAgo),
        ]);
});

// ── Reorder point: formula or manual fallback ────────────────────

it('uses the manual reorder point when there are fewer than 5 sales', function () {
    $needy = Branch::factory()->create();
    $rich = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($needy, $product, '5', '10');
    ($this->stockAt)($rich, $product, '100', '5');
    ($this->sales)($needy, $product, 4, '-2', 5);

    $suggestion = ($this->suggestionFor)($needy, $product);

    expect($suggestion)->not->toBeNull();
    expect($suggestion['reorder_point']->value)->toBe('10.000');
});

it('computes the reorder point from the last 30 days of sales', function () {
    $needy = Branch::factory()->create();
    $rich = Branch::factory()->create();
    $product = Product::factory()->create();

    // The manual value (99) must be ignored once there is enough history.
    ($this->stockAt)($needy, $product, '3', '99');
    ($this->stockAt)($rich, $product, '100', '5');

    // 6 sales × 10 units = 60 sold → 60 / 30 = 2.000 per day
    ($this->sales)($needy, $product, 6, '-10', 5);

    $suggestion = ($this->suggestionFor)($needy, $product);

    // ROP = usage × lead time + usage × safety buffer = 2 × 2 + 2 × 1 = 6.000
    expect($suggestion)->not->toBeNull();
    expect($suggestion['reorder_point']->value)->toBe('6.000');
});

it('ignores sales older than 30 days', function () {
    $needy = Branch::factory()->create();
    $rich = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($needy, $product, '5', '10');
    ($this->stockAt)($rich, $product, '100', '5');

    // 10 old sales do not count. Only 3 recent ones, below the trust threshold.
    ($this->sales)($needy, $product, 10, '-3', 40);
    ($this->sales)($needy, $product, 3, '-3', 5);

    $suggestion = ($this->suggestionFor)($needy, $product);

    expect($suggestion['reorder_point']->value)->toBe('10.000');
});

// ── Suggestions ──────────────────────────────────────────────────

it('suggests nothing when stock is above the reorder point', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($branch, $product, '100', '10');

    expect($this->service->suggestAllTransfers())->toBeEmpty();
});

it('suggests a transfer from the branch with the most surplus', function () {
    $needy = Branch::factory()->create();
    $rich = Branch::factory()->create();
    $poor = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($needy, $product, '2', '10');
    ($this->stockAt)($rich, $product, '80', '5');   // surplus 75
    ($this->stockAt)($poor, $product, '8', '5');    // surplus 3

    $suggestion = ($this->suggestionFor)($needy, $product);

    expect($suggestion)->not->toBeNull();
    expect($suggestion['from_branch']->id)->toBe($rich->id);
    expect($suggestion['to_branch']->id)->toBe($needy->id);
    // Needs 10 - 2 = 8, and the source can spare 75, so the quantity is 8.
    expect($suggestion['quantity']->value)->toBe('8.000');
});

it('caps the quantity at what the source can spare', function () {
    $needy = Branch::factory()->create();
    $source = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($needy, $product, '0', '50');
    ($this->stockAt)($source, $product, '20', '10'); // surplus only 10

    $suggestion = ($this->suggestionFor)($needy, $product);

    // Needs 50, but the source can only spare 10.
    expect($suggestion['quantity']->value)->toBe('10.000');
});

it('suggests nothing when no branch has surplus', function () {
    $needy = Branch::factory()->create();
    $other = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($needy, $product, '1', '10');
    ($this->stockAt)($other, $product, '3', '10'); // also below its own reorder point

    expect($this->service->suggestAllTransfers())->toBeEmpty();
});

it('reduces what a source can share by its reserved stock', function () {
    $needy = Branch::factory()->create();
    $source = Branch::factory()->create();
    $elsewhere = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($needy, $product, '0', '100');
    ($this->stockAt)($source, $product, '50', '10');

    // 20 units at the source are already promised to a third branch.
    $order = TransferOrder::factory()->create([
        'from_branch_id' => $source->id,
        'to_branch_id' => $elsewhere->id,
        'status' => TransferStatus::Requested,
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity_requested' => '20']);

    $suggestion = ($this->suggestionFor)($needy, $product);

    // Available = 50 - 20 = 30, surplus = 30 - 10 = 20.
    expect($suggestion['quantity']->value)->toBe('20.000');
});

it('returns a suggestion for every branch below its reorder point', function () {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($branchA, $product, '2', '10');
    ($this->stockAt)($branchB, $product, '100', '5');

    $suggestions = $this->service->suggestAllTransfers();

    expect($suggestions)->toHaveCount(1);
    expect($suggestions->first()['to_branch']->id)->toBe($branchA->id);
    expect($suggestions->first()['from_branch']->id)->toBe($branchB->id);
});

// ── Incoming stock ───────────────────────────────────────────────

it('does not suggest again when a pending transfer already covers the shortage', function () {
    $needy = Branch::factory()->create();
    $rich = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($needy, $product, '2', '10');
    ($this->stockAt)($rich, $product, '100', '5');

    // 8 units are already on their way: position = 2 + 8 = 10, which meets the reorder point.
    $order = TransferOrder::factory()->create([
        'from_branch_id' => $rich->id,
        'to_branch_id' => $needy->id,
        'status' => TransferStatus::Requested,
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity_requested' => '8']);

    expect(($this->suggestionFor)($needy, $product))->toBeNull();
});

it('counts only the quantity actually sent once a transfer is in transit', function () {
    $needy = Branch::factory()->create();
    $rich = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($needy, $product, '2', '10');
    ($this->stockAt)($rich, $product, '100', '5');

    // 8 were requested but only 3 were shipped, so only 3 will arrive.
    $order = TransferOrder::factory()->create([
        'from_branch_id' => $rich->id,
        'to_branch_id' => $needy->id,
        'status' => TransferStatus::InTransit,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity_requested' => '8',
        'quantity_sent' => '3',
    ]);

    $suggestion = ($this->suggestionFor)($needy, $product);

    // Position = 2 + 3 = 5, so it still needs 10 - 5 = 5.
    expect($suggestion)->not->toBeNull();
    expect($suggestion['incoming']->value)->toBe('3.000');
    expect($suggestion['quantity']->value)->toBe('5.000');
});

it('stops counting a transfer once it has been received', function () {
    $needy = Branch::factory()->create();
    $rich = Branch::factory()->create();
    $product = Product::factory()->create();

    ($this->stockAt)($needy, $product, '2', '10');
    ($this->stockAt)($rich, $product, '100', '5');

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $rich->id,
        'to_branch_id' => $needy->id,
        'status' => TransferStatus::Received,
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity_requested' => '8']);

    $suggestion = ($this->suggestionFor)($needy, $product);

    // Received stock is already in the branch quantity, so nothing is incoming.
    expect($suggestion['incoming']->compare(0))->toBe(0);
});
