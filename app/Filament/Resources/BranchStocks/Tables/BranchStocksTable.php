<?php

namespace App\Filament\Resources\BranchStocks\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BranchStocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name')->label('Branch')->sortable()->searchable(),
                TextColumn::make('product.name')->label('Product')->sortable()->searchable(),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->formatStateUsing(fn ($state) => $state?->value)
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->label('Sale Price')
                    ->formatStateUsing(fn ($state) => $state?->value),
                TextColumn::make('purchase_price')
                    ->label('Purchase Price')
                    ->formatStateUsing(fn ($state) => $state?->value),
                TextColumn::make('reorder_point')
                    ->label('Reorder Point')
                    ->formatStateUsing(fn ($state) => $state?->value),
                TextColumn::make('reorder_qty')
                    ->label('Reorder Qty')
                    ->formatStateUsing(fn ($state) => $state?->value),
                TextColumn::make('created_at')->label('Created at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
