<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SkuGeneratorService;
class Product extends Model
{
    use HasFactory;
    protected $fillable = ['sku', 'name', 'category_id', 'unit', 'is_active'];

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_stock')
            ->withPivot(['quantity', 'sale_price', 'purchase_price', 'reorder_point', 'reorder_qty'])
            ->withTimestamps();
    }

    public function category(): BelongsTo{
        return $this->belongsTo(Category::class);
    }

}
