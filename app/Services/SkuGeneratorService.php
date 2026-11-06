<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class SkuGeneratorService
{
    public function generate(Category $category): string
    {
        return DB::transaction(function () use ($category) {

            // Re-fetch and lock the row INSIDE the transaction.
            // Locking the $category instance you were passed (which was
            // fetched before the transaction started) wouldn't protect
            // against another request reading the same stale value —
            // the lock only matters on a read that happens after the
            // transaction begins.
            $locked = Category::where('id', $category->id)
                ->lockForUpdate()
                ->first();

            $nextSeq = $locked->last_sku_sequence + 1;
            $locked->update(['last_sku_sequence' => $nextSeq]);
            return $locked->code . '-' . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);
        });
    }
}
