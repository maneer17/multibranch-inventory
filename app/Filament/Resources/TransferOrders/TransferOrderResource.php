<?php

namespace App\Filament\Resources\TransferOrders;

use App\Filament\Resources\TransferOrders\Pages\ViewTransferOrder;
use App\Filament\Resources\TransferOrders\Pages\ListTransferOrders;
use App\Filament\Resources\TransferOrders\Tables\TransferOrdersTable;
use App\Models\TransferOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransferOrderResource extends Resource
{
    protected static ?string $model = TransferOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 0;

    public static function getNavigationGroup(): ?string
    {
        return __('inventory.nav.transfers');
    }

    public static function getModelLabel(): string
    {
        return __('inventory.resource.transfer_order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inventory.resource.transfer_orders');
    }

    public static function table(Table $table): Table
    {
        return TransferOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransferOrders::route('/'),
            'view' => ViewTransferOrder::route('/{record}'),
        ];
    }
}
