<?php

use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use App\Models\User;
use App\Services\StockMovementService;
use App\Services\TransferOrderService;
use BcMath\Number;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);


beforeEach(function () {
    $this->service = new TransferOrderService(new StockMovementService());
});
// --- createRequest() ---

it('rejects a transfer where both branches are the same', function () {
    $branch = Branch::factory()->create();
    $items = [];
    $user = User::factory()->create();

    expect(fn () => $this->service->createRequest(
        $branch,
        $branch,
        $user,
        $items,
    ))->toThrow(\InvalidArgumentException::class);
});

it('rejects a transfer with no items', function () {
    $from = Branch::factory()->create();
    $to = Branch::factory()->create();
    $items = [];
    $user = User::factory()->create();

    expect(fn () => $this->service->createRequest(
        $from,
        $to,
        $user,
        $items,
    ))->toThrow(\InvalidArgumentException::class);
});

it('rejects a quantity of zero', function () {
    $from = Branch::factory()->create();
    $to = Branch::factory()->create();
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $items = [
        ['product' => $product, 'quantity' => new Number('0')],
    ];


    expect(fn () => $this->service->createRequest(
        $from,
        $to,
        $user,
        $items,
    ))->toThrow(\InvalidArgumentException::class);
});

it('creates a request with the correct status and quantity', function () {
    $from = Branch::factory()->create();
    $to = Branch::factory()->create();
    $product = Product::factory()->create();
    $user = User::factory()->create();

    BranchStock::factory()->create(['branch_id' => $from->id, 'product_id' => $product->id, 'quantity' => '20.000']);
    BranchStock::factory()->create(['branch_id' => $to->id, 'product_id' => $product->id, 'quantity' => '0.000']);

    $items = [
        ['product' => $product, 'quantity' => new Number('5')],
    ];

    $order = $this->service->createRequest($from, $to, $user, $items);

    expect($order->status)->toBe(TransferStatus::Requested);
    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->quantity_requested->value)->toBe('5.000');
});

it('rejects a request for more than what is currently available', function () {
    $from = Branch::factory()->create();
    $to = Branch::factory()->create();
    $product = Product::factory()->create();
    $user = User::factory()->create();

    BranchStock::factory()->create(['branch_id' => $from->id, 'product_id' => $product->id, 'quantity' => '10.000']);
    BranchStock::factory()->create(['branch_id' => $to->id, 'product_id' => $product->id, 'quantity' => '0.000']);


    $this->service->createRequest($from, $to, $user, [
        ['product' => $product, 'quantity' => new Number('10')],
    ]);

    expect(fn () => $this->service->createRequest($from, $to, $user, [
        ['product' => $product, 'quantity' => new Number('1')],
    ]))->toThrow(\DomainException::class);
});

// --- approve() / reject() / cancel() ---

it('rejects approving an already received order', function () {
    $order = TransferOrder::factory()->create(['status' => TransferStatus::Received]);
    $approver = User::factory()->create();

    expect(fn () => $this->service->approve($order, $approver))->toThrow(\DomainException::class);
});

it('cancels a requested order', function () {
    $order = TransferOrder::factory()->create();

    expect($this->service->cancel($order)->status)->toBe(TransferStatus::Cancelled);
});
it('ships a transfer', function () {
    $from = Branch::factory()->create();
    $to = Branch::factory()->create();
    $product = Product::factory()->create();
    $user =User::factory()->create();
    BranchStock::factory()->create(['branch_id' => $from->id, 'product_id' => $product->id, 'quantity' => '50.000']);
    BranchStock::factory()->create(['branch_id' => $to->id, 'product_id' => $product->id, 'quantity' => '0.000']);

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $from->id,
        'to_branch_id' => $to->id,
        'status' => TransferStatus::Approved,
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'quantity_requested' => new Number('50'),
    ]);

    expect($this->service->ship($order, $user)->status)->toBe(TransferStatus::InTransit);

    $item = $order->items->firstWhere('product_id', $product->id);
    expect($item->quantity_sent->value)->toBe('50.000');
    $newStock = StockMovement::where('product_id', $product->id)->
        where('branch_id', $from->id)->first();
    expect($newStock->type)->toBe(MovementType::TransferOut);
    $fromStock = BranchStock::where('branch_id', $from->id)->where('product_id', $product->id)->first();
    expect($fromStock->quantity->value)->toBe('0.000');

});


it('receives a transfer with fewer units than were sent', function () {
    $from = Branch::factory()->create();
    $to = Branch::factory()->create();
    $product = Product::factory()->create();
    $user = User::factory()->create();

    BranchStock::factory()->create(['branch_id' => $from->id, 'product_id' => $product->id, 'quantity' => '50.000']);
    BranchStock::factory()->create(['branch_id' => $to->id, 'product_id' => $product->id, 'quantity' => '0.000']);

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $from->id,
        'to_branch_id' => $to->id,
        'status' => TransferStatus::InTransit,
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'quantity_requested' => new Number('50'),
        'quantity_sent' => new Number('50'),
    ]);

    // Received deliberately differs from sent — the real case the
    // override exists for, not just the default-to-sent path.
    $result = $this->service->receive($order, $user, [$product->id => new Number('48')]);

    expect($result->status)->toBe(TransferStatus::Received);

    $item = $order->items->firstWhere('product_id', $product->id);
    expect($item->quantity_received->value)->toBe('48.000');

    $movement = StockMovement::where('product_id', $product->id)
        ->where('branch_id', $to->id)
        ->first();
    expect($movement->type)->toBe(MovementType::TransferIn);

    $toStock = BranchStock::where('branch_id', $to->id)->where('product_id', $product->id)->first();
    expect($toStock->quantity->value)->toBe('48.000');
});

