<?php

namespace App\Models;

use App\Enums\TransferStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferOrder extends Model
{
    use HasFactory;

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $user->hasRole('admin')
            ? $query
            : $query->where(fn (Builder $q) => $q
                ->where('from_branch_id', $user->branch_id)
                ->orWhere('to_branch_id', $user->branch_id));
    }
    protected $fillable = ['from_branch_id', 'to_branch_id', 'requested_by', 'approved_by', 'status'];

    protected $casts = [
        'status' => TransferStatus::class,
    ];

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    // Named requester/approver, not requested_by/approved_by: a relation
    // can't share a name with the column that holds its foreign key.
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferOrderItem::class);
    }
}
