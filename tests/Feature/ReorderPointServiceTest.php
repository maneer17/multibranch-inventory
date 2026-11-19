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
use BcMath\Number;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ReorderPointService();
    $this->user = User::factory()->create();
});

// ── averageDailyUsage ────────────────────────────────────────────

it('returns null when fewer than 5 sales exist', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '100',
    ]);

    for ($i = 0; $i < 4; $i++) {
        StockMovement::factory()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => MovementType::Sale,
            'quantity' => '-2',
            'created_by' => $this->user->id,
            'created_at' => now()->subDays(rand(1, 20)),
        ]);
    }

    expect($this->service->averageDailyUsage($branch, $product))->toBeNull();
});

it('computes average daily usage from sale history', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '200',
    ]);

    // 6 sales, each 5 units = 30 total sold over 30-day window
    for ($i = 0; $i < 6; $i++) {
        StockMovement::factory()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => MovementType::Sale,
            'quantity' => '-5',
            'created_by' => $this->user->id,
            'created_at' => now()->subDays(rand(1, 25)),
        ]);
    }

    $usage = $this->service->averageDailyUsage($branch, $product);

    // 30 units / 30 days = 1.000
    expect($usage)->not->toBeNull();
    expect($usage->value)->toBe('1.000');
});

it('ignores sales older than 30 days', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '200',
    ]);

    // 10 old sales — should be excluded
    for ($i = 0; $i < 10; $i++) {
        StockMovement::factory()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => MovementType::Sale,
            'quantity' => '-3',
            'created_by' => $this->user->id,
            'created_at' => now()->subDays(40),
        ]);
    }

    // Only 3 recent — below trust threshold
    for ($i = 0; $i < 3; $i++) {
        StockMovement::factory()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => MovementType::Sale,
            'quantity' => '-3',
            'created_by' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);
    }

    expect($this->service->averageDailyUsage($branch, $product))->toBeNull();
});

// ── reorderPoint ─────────────────────────────────────────────────

it('uses formula when enough sales history exists', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '200',
        'reorder_point' => '99',
    ]);

    // 6 sales × 10 units = 60 total → 60/30 = 2.000/day
    for ($i = 0; $i < 6; $i++) {
        StockMovement::factory()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => MovementType::Sale,
            'quantity' => '-10',
            'created_by' => $this->user->id,
            'created_at' => now()->subDays(rand(1, 25)),
        ]);
    }

    $rop = $this->service->reorderPoint($branch, $product);

    // ROP = usage × lead_time + usage × safety_buffer
    //     = 2.000 × 2 + 2.000 × 1 = 6.000
    expect($rop->value)->toBe('6.000');
});

it('falls back to manual reorder_point when not enough sales', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '10',
        'reorder_point' => '15.500',
    ]);

    $rop = $this->service->reorderPoint($branch, $product);

    expect($rop->value)->toBe('15.500');
});

// ── available ────────────────────────────────────────────────────

it('returns null when product is not carried at branch', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();

    expect($this->service->available($branch, $product))->toBeNull();
});

it('subtracts reserved quantity from stock', function () {
    $from = Branch::factory()->create();
    $to = Branch::factory()->create();
    $product = Product::factory()->create();

    BranchStock::factory()->create([
        'branch_id' => $from->id,
        'product_id' => $product->id,
        'quantity' => '50',
    ]);

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $from->id,
        'to_branch_id' => $to->id,
        'status' => TransferStatus::Requested,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity_requested' => '20',
    ]);

    $available = $this->service->available($from, $product);

    // 50 - 20 reserved = 30
    expect($available->value)->toBe('30.000');
});

// ── suggestTransfer ──────────────────────────────────────────────

it('returns null when stock is above reorder point', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '100',
        'reorder_point' => '10',
    ]);

    expect($this->service->suggestTransfer($branch, $product))->toBeNull();
});

it('suggests a transfer from the branch with most surplus', function () {
    $needyBranch = Branch::factory()->create();
    $richBranch = Branch::factory()->create();
    $poorBranch = Branch::factory()->create();
    $product = Product::factory()->create();

    // Needy branch: quantity 2, manual reorder_point 10
    BranchStock::factory()->create([
        'branch_id' => $needyBranch->id,
        'product_id' => $product->id,
        'quantity' => '2',
        'reorder_point' => '10',
        'reorder_qty' => '0',
    ]);

    // Rich branch: quantity 80, manual reorder_point 5
    BranchStock::factory()->create([
        'branch_id' => $richBranch->id,
        'product_id' => $product->id,
        'quantity' => '80',
        'reorder_point' => '5',
    ]);

    // Poor branch: quantity 8, manual reorder_point 5
    BranchStock::factory()->create([
        'branch_id' => $poorBranch->id,
        'product_id' => $product->id,
        'quantity' => '8',
        'reorder_point' => '5',
    ]);

    $suggestion = $this->service->suggestTransfer($needyBranch, $product);

    expect($suggestion)->not->toBeNull();
    expect($suggestion['from_branch']->id)->toBe($richBranch->id);
    expect($suggestion['to_branch']->id)->toBe($needyBranch->id);
    // Needs 10 - 2 = 8, rich has surplus 80 - 5 = 75. Quantity = min(8, 75) = 8.
    expect($suggestion['quantity']->value)->toBe('8.000');
});

it('caps transfer quantity at source surplus', function () {
    $needyBranch = Branch::factory()->create();
    $sourceBranch = Branch::factory()->create();
    $product = Product::factory()->create();

    // Needy: qty 0, reorder_point 50, reorder_qty 0
    BranchStock::factory()->create([
        'branch_id' => $needyBranch->id,
        'product_id' => $product->id,
        'quantity' => '0',
        'reorder_point' => '50',
        'reorder_qty' => '0',
    ]);

    // Source: qty 20, reorder_point 10 → surplus only 10
    BranchStock::factory()->create([
        'branch_id' => $sourceBranch->id,
        'product_id' => $product->id,
        'quantity' => '20',
        'reorder_point' => '10',
    ]);

    $suggestion = $this->service->suggestTransfer($needyBranch, $product);

    expect($suggestion)->not->toBeNull();
    // Needs 50 but source can only spare 10
    expect($suggestion['quantity']->value)->toBe('10.000');
});

it('returns null when no branch has surplus', function () {
    $needyBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create();
    $product = Product::factory()->create();

    BranchStock::factory()->create([
        'branch_id' => $needyBranch->id,
        'product_id' => $product->id,
        'quantity' => '1',
        'reorder_point' => '10',
    ]);

    // Other branch is also below its own reorder point
    BranchStock::factory()->create([
        'branch_id' => $otherBranch->id,
        'product_id' => $product->id,
        'quantity' => '3',
        'reorder_point' => '10',
    ]);

    expect($this->service->suggestTransfer($needyBranch, $product))->toBeNull();
});

// ── suggestAllTransfers (bulk path) ──────────────────────────────

it('returns suggestions for all branches below reorder point', function () {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();
    $product = Product::factory()->create();

    // A is low, B has surplus
    BranchStock::factory()->create([
        'branch_id' => $branchA->id,
        'product_id' => $product->id,
        'quantity' => '2',
        'reorder_point' => '10',
        'reorder_qty' => '0',
    ]);

    BranchStock::factory()->create([
        'branch_id' => $branchB->id,
        'product_id' => $product->id,
        'quantity' => '100',
        'reorder_point' => '5',
    ]);

    $suggestions = $this->service->suggestAllTransfers();

    expect($suggestions)->toHaveCount(1);
    expect($suggestions->first()['to_branch']->id)->toBe($branchA->id);
    expect($suggestions->first()['from_branch']->id)->toBe($branchB->id);
});
