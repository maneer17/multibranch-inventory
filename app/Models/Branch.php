<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Branch extends Model
{
    use HasFactory;

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $user->hasRole('admin')
            ? $query
            : $query->where('id', $user->branch_id);
    }
    protected $fillable = ['code', 'name', 'address', 'city', 'is_active'];
    protected $casts = [
        'address' => 'array',
        'is_active' => 'boolean',
    ];
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'branch_stock')
            ->withPivot(['quantity', 'sale_price', 'purchase_price', 'reorder_point', 'reorder_qty'])
            ->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
