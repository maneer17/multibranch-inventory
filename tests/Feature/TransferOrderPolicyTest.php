<?php

use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\TransferOrder;
use App\Models\User;
use App\Policies\TransferOrderPolicy;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('branch_manager');
    Role::findOrCreate('staff');

    $this->policy = new TransferOrderPolicy();
});

// --- createRequest ---

it('allows a staff member to request a transfer from their own branch', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('staff');

    expect($this->policy->createRequest($user, $branch->id))->toBeTrue();
});

it('denies a staff member requesting a transfer from a branch they do not belong to', function () {
    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $ownBranch->id]);
    $user->assignRole('staff');

    expect($this->policy->createRequest($user, $otherBranch->id))->toBeFalse();
});

it('allows an admin to request a transfer from any branch, having no branch of their own', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => null]);
    $admin->assignRole('admin');

    expect($this->policy->createRequest($admin, $branch->id))->toBeTrue();
});

it('denies a user with no role at all', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);

    expect($this->policy->createRequest($user, $branch->id))->toBeFalse();
});

// --- approve ---

it('denies staff from approving a request, regardless of branch or requester', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('staff');

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $branch->id,
        'requested_by' => User::factory()->create()->id,
        'status' => TransferStatus::Requested,
    ]);

    expect($this->policy->approve($user, $order))->toBeFalse();
});

it('denies a manager approving their own request, even from the correct branch', function () {
    $branch = Branch::factory()->create();
    $manager = User::factory()->create(['branch_id' => $branch->id]);
    $manager->assignRole('branch_manager');

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $branch->id,
        'requested_by' => $manager->id,
        'status' => TransferStatus::Requested,
    ]);

    expect($this->policy->approve($manager, $order))->toBeFalse();
});

it('denies a manager approving a request from a different branch', function () {
    $managerBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create();
    $manager = User::factory()->create(['branch_id' => $managerBranch->id]);
    $manager->assignRole('branch_manager');

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $otherBranch->id,
        'requested_by' => User::factory()->create()->id,
        'status' => TransferStatus::Requested,
    ]);

    expect($this->policy->approve($manager, $order))->toBeFalse();
});

it('allows a manager to approve a request from their own branch, made by someone else', function () {
    $branch = Branch::factory()->create();
    $manager = User::factory()->create(['branch_id' => $branch->id]);
    $manager->assignRole('branch_manager');

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $branch->id,
        'requested_by' => User::factory()->create()->id,
        'status' => TransferStatus::Requested,
    ]);

    expect($this->policy->approve($manager, $order))->toBeTrue();
});

// --- reject (mirrors approve, since it delegates to it) ---

it('denies a manager rejecting their own request', function () {
    $branch = Branch::factory()->create();
    $manager = User::factory()->create(['branch_id' => $branch->id]);
    $manager->assignRole('branch_manager');

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $branch->id,
        'requested_by' => $manager->id,
        'status' => TransferStatus::Requested,
    ]);

    expect($this->policy->reject($manager, $order))->toBeFalse();
});

it('allows a manager to reject a request from their own branch, made by someone else', function () {
    $branch = Branch::factory()->create();
    $manager = User::factory()->create(['branch_id' => $branch->id]);
    $manager->assignRole('branch_manager');

    $order = TransferOrder::factory()->create([
        'from_branch_id' => $branch->id,
        'requested_by' => User::factory()->create()->id,
        'status' => TransferStatus::Requested,
    ]);

    expect($this->policy->reject($manager, $order))->toBeTrue();
});

// --- cancel ---

it('allows staff to cancel a request from their own branch', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('staff');

    $order = TransferOrder::factory()->create(['from_branch_id' => $branch->id]);

    expect($this->policy->cancel($user, $order))->toBeTrue();
});

it('denies cancelling a request from a branch the user does not belong to', function () {
    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $ownBranch->id]);
    $user->assignRole('staff');

    $order = TransferOrder::factory()->create(['from_branch_id' => $otherBranch->id]);

    expect($this->policy->cancel($user, $order))->toBeFalse();
});

// --- ship ---

it('allows staff to ship from their own branch', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('staff');

    $order = TransferOrder::factory()->create(['from_branch_id' => $branch->id]);

    expect($this->policy->ship($user, $order))->toBeTrue();
});

it('denies shipping from a branch the user does not belong to', function () {
    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $ownBranch->id]);
    $user->assignRole('staff');

    $order = TransferOrder::factory()->create(['from_branch_id' => $otherBranch->id]);

    expect($this->policy->ship($user, $order))->toBeFalse();
});

// --- receive ---

it('allows staff to receive at their own branch', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('staff');

    $order = TransferOrder::factory()->create(['to_branch_id' => $branch->id]);

    expect($this->policy->receive($user, $order))->toBeTrue();
});

it('denies receiving at a branch the user does not belong to', function () {
    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $ownBranch->id]);
    $user->assignRole('staff');

    $order = TransferOrder::factory()->create(['to_branch_id' => $otherBranch->id]);

    expect($this->policy->receive($user, $order))->toBeFalse();
});
