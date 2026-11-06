<?php

namespace App\Filament\Resources\BranchStocks;

use App\Filament\Resources\BranchStocks\Pages\ListBranchStocks;
use App\Filament\Resources\BranchStocks\Tables\BranchStocksTable;
use App\Models\BranchStock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BranchStockResource extends Resource
{
    protected static ?string $model = BranchStock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'branch-stock';

    public static function table(Table $table): Table
    {
        return BranchStocksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranchStocks::route('/'),
        ];
    }
}
