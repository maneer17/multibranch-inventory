<?php

namespace Database\Factories;

use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'from_branch_id' => Branch::factory(),
            'to_branch_id' => Branch::factory(),
            'requested_by' => User::factory(),
            'approved_by' => null,
            'status' => TransferStatus::Requested,
        ];
    }
}
