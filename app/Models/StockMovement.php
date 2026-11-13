<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Casts\AsDecimal;

class StockMovement extends Model
{
    use HasFactory;
    public const UPDATED_AT = null;

    protected $casts = [
        'quantity' => AsDecimal::class,
        'type' => MovementType::class,
        'unit_price' => AsDecimal::class
    ];

    protected $fillable = ['product_id', 'quantity', 'created_by', 'type', 'branch_id', 'note', 'transfer_order_id', 'unit_price'];

    public function transferOrder(): BelongsTo
    {
        return $this->belongsTo(TransferOrder::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
