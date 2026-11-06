<?php

namespace App\Models;

use App\Casts\AsDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Enums\TransferStatus;
class BranchStock extends Model
{
    use HasFactory;
    protected $table = 'branch_stock';

    protected $fillable = ['branch_id', 'product_id', 'quantity', 'sale_price', 'purchase_price', 'reorder_point', 'reorder_qty'];

    protected function casts(): array
    {
        return [
            'quantity' => AsDecimal::class,
            'sale_price' => AsDecimal::class,
            'purchase_price' => AsDecimal::class,
            'reorder_point' => AsDecimal::class,
            'reorder_qty' => AsDecimal::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    // On BranchStock
    protected function reservedQuantity(): Attribute
    {
        return Attribute::make(
            get: fn () => TransferOrderItem::query()
                ->where('product_id', $this->product_id)
                ->whereHas('transferOrder', function ($q) {
                    $q->where('from_branch_id', $this->branch_id)
                        ->whereIn('status', [TransferStatus::Requested, TransferStatus::Approved]);
                })
                ->sum('quantity_requested'),
        );
    }
}
