<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Filament\Resources\StockMovements\Actions\RecordMovementAction;
use App\Filament\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    public function getSubheading(): ?string
    {
        return __('inventory.subheading.stock_movements');
    }

    protected function getHeaderActions(): array
    {
        return [
            RecordMovementAction::make(),
        ];
    }
}
