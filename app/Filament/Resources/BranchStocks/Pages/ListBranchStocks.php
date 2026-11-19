<?php

namespace App\Filament\Resources\BranchStocks\Pages;

use App\Filament\Resources\BranchStocks\BranchStockResource;
use Filament\Resources\Pages\ListRecords;

class ListBranchStocks extends ListRecords
{
    protected static string $resource = BranchStockResource::class;

    public function getSubheading(): ?string
    {
        return __('inventory.subheading.stock_levels');
    }
}
