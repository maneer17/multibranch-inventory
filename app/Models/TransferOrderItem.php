<?php

namespace App\Models;

use App\Casts\AsDecimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferOrderItem extends Model
{
    protected $fillable = ['product_id', 'transfer_order_id', 'quantity_requested', 'quantity_received', 'quantity_sent'];
    protected $casts = [
        'quantity_requested' => AsDecimal::class,
        'quantity_received' => AsDecimal::class,
        'quantity_sent' => AsDecimal::class,
    ];
    public function transferOrder(): BelongsTo{
        return $this->belongsTo(TransferOrder::class, 'transfer_order_id');
    }
    public function product(): BelongsTo{
        return $this->belongsTo(Product::class, 'product_id');
    }

}
