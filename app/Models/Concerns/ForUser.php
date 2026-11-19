<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ForUser
{
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $user->hasRole('admin')
            ? $query
            : $query->where('branch_id', $user->branch_id);
    }
}
