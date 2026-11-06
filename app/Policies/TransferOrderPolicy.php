<?php

namespace App\Policies;

use App\Enums\TransferStatus;
use App\Models\TransferOrder;
use App\Models\User;

class TransferOrderPolicy
{
    public function createRequest(User $user, int $fromBranchId): bool
    {
        return $this->hasAnyRole($user) && $this->belongsToBranch($user, $fromBranchId);
    }

    public function approve(User $user, TransferOrder $order): bool
    {
        if (! $user->hasAnyRole(['admin', 'branch_manager'])) {
            return false;
        }

        if ($order->requested_by === $user->id) {
            return false;
        }

        return $this->belongsToBranch($user, $order->from_branch_id);
    }

    public function reject(User $user, TransferOrder $order): bool
    {
        // Same rule as approve: a rejection is the same decision, just
        // the other outcome — same people, same branch, same self-check.
        return $this->approve($user, $order);
    }

    public function cancel(User $user, TransferOrder $order): bool
    {
        return $this->hasAnyRole($user) && $this->belongsToBranch($user, $order->from_branch_id);
    }

    public function ship(User $user, TransferOrder $order): bool
    {
        return $this->hasAnyRole($user) && $this->belongsToBranch($user, $order->from_branch_id);
    }

    public function receive(User $user, TransferOrder $order): bool
    {
        return $this->hasAnyRole($user) && $this->belongsToBranch($user, $order->to_branch_id);
    }

    private function hasAnyRole(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'branch_manager', 'staff']);
    }

    // Admin has no branch_id, meaning "every branch" — this is the one
    // place that exemption is expressed, so every method above inherits
    // it automatically rather than each one special-casing Admin itself.
    private function belongsToBranch(User $user, int $branchId): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->branch_id === $branchId;
    }
}
