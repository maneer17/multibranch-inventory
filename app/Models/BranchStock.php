<?php

namespace App\Models;

use App\Casts\AsDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BcMath\Number;
use App\Enums\TransferStatus;
use App\Models\TransferOrderItem;
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

    public function reservedQuantity(): Number
    {
        $sum = TransferOrderItem::query()
            ->where('product_id', $this->product_id)
            ->whereHas('transferOrder', fn($q) => $q
                ->where('from_branch_id', $this->branch_id)
                ->whereIn('status', [
                    TransferStatus::Requested->value,
                    TransferStatus::Approved->value,
                ]))
            ->get()
            ->reduce(
                fn(Number $carry, TransferOrderItem $item) => $carry->add($item->quantity_requested),
                new Number('0'),
            );

        return $sum;
    }

    public function available(): Number
    {
        return $this->quantity->sub($this->reservedQuantity());
    }
}
