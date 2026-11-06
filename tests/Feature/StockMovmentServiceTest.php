<?php

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\User;
use App\Services\StockMovementService;
use BcMath\Number;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('updates the branch stock to 7 after a sale movement of 3', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $branchStock = BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '10.000',
    ]);

    $service = new StockMovementService();

    $movement = $service->submit(
        $product,
        $branch,
        $user,
        new Number('3'),
        MovementType::Sale,
    );

    $branchStock->refresh();

    expect($branchStock->quantity->value)->toBe('7.000');
    expect($movement->quantity->value)->toBe('-3.000');
});

it('rejects a movement that would take stock below zero, and rolls back', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $branchStock = BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '2.000',
    ]);

    $service = new StockMovementService();

    expect(fn () => $service->submit(
        $product,
        $branch,
        $user,
        new Number('5'),
        MovementType::Sale,
    ))->toThrow(\DomainException::class);

    // Re-read from the database — proves the transaction actually rolled
    // back, not just that an exception was thrown somewhere.
    $branchStock->refresh();

    expect($branchStock->quantity->value)->toBe('2.000');
    expect(\App\Models\StockMovement::count())->toBe(0);
});
