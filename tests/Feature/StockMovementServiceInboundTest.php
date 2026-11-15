<?php

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\User;
use App\Services\StockMovementService;
use BcMath\Number;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new StockMovementService();
    $this->branch = Branch::factory()->create();
    $this->product = Product::factory()->create();
    $this->user = User::factory()->create();

    $this->stock = BranchStock::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'quantity' => '10.000',
    ]);
});

it('increases stock on PurchaseReceipt', function () {
    $movement = $this->service->submit(
        $this->product, $this->branch, $this->user,
        new Number('25'),
        MovementType::PurchaseReceipt,
    );

    $this->stock->refresh();

    expect($this->stock->quantity->value)->toBe('35.000');
    expect($movement->quantity->value)->toBe('25.000');
    expect($movement->type)->toBe(MovementType::PurchaseReceipt);
});

it('increases stock on AdjustmentIn', function () {
    $movement = $this->service->submit(
        $this->product, $this->branch, $this->user,
        new Number('3'),
        MovementType::AdjustmentIn,
    );

    $this->stock->refresh();

    expect($this->stock->quantity->value)->toBe('13.000');
    expect($movement->quantity->value)->toBe('3.000');
});

it('increases stock on InitialStockUpload', function () {
    $movement = $this->service->submit(
        $this->product, $this->branch, $this->user,
        new Number('50'),
        MovementType::InitialStockUpload,
    );

    $this->stock->refresh();

    expect($this->stock->quantity->value)->toBe('60.000');
    expect($movement->quantity->value)->toBe('50.000');
});

it('stores the unit price when provided', function () {
    $movement = $this->service->submit(
        $this->product, $this->branch, $this->user,
        new Number('10'),
        MovementType::PurchaseReceipt,
        unitPrice: new Number('4.50'),
    );

    expect($movement->unit_price->value)->toBe('4.50');
});

it('rejects zero quantity', function () {
    expect(fn () => $this->service->submit(
        $this->product, $this->branch, $this->user,
        new Number('0'),
        MovementType::PurchaseReceipt,
    ))->toThrow(\InvalidArgumentException::class);
});

it('rejects movement when branch does not carry the product', function () {
    $otherProduct = Product::factory()->create();

    expect(fn () => $this->service->submit(
        $otherProduct, $this->branch, $this->user,
        new Number('5'),
        MovementType::PurchaseReceipt,
    ))->toThrow(\DomainException::class, 'No branch_stock row exists');
});

it('decreases stock on AdjustmentOut', function () {
    $movement = $this->service->submit(
        $this->product, $this->branch, $this->user,
        new Number('4'),
        MovementType::AdjustmentOut,
    );

    $this->stock->refresh();

    expect($this->stock->quantity->value)->toBe('6.000');
    expect($movement->quantity->value)->toBe('-4.000');
});
