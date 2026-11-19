<?php

namespace App\Filament\Resources\BranchStocks;

use App\Filament\Resources\BranchStocks\Pages\ListBranchStocks;
use App\Filament\Resources\BranchStocks\Tables\BranchStocksTable;
use App\Models\BranchStock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BranchStockResource extends Resource
{
    protected static ?string $model = BranchStock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('inventory.nav.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory.resource.stock_levels');
    }

    public static function getModelLabel(): string
    {
        return __('inventory.resource.stock_level');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inventory.resource.stock_levels');
    }

    public static function table(Table $table): Table
    {
        return BranchStocksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['branch', 'product'])
            ->forUser(auth()->user());
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
