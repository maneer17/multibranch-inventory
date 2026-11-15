<?php

namespace App\Filament\Resources\BranchStocks\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BranchStocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('quantity', 'asc')
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->weight('bold')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->color(fn ($record) => $record->quantity->compare($record->reorder_point) < 0 ? 'danger' : null),
                TextColumn::make('sale_price')
                    ->label('Sale Price')
                    ->money('USD'),
                TextColumn::make('purchase_price')
                    ->label('Purchase Price')
                    ->money('USD'),
                TextColumn::make('reorder_point')
                    ->label('Reorder Point')
                    ->numeric(decimalPlaces: 3)
                    ->color('warning'),
                TextColumn::make('reorder_qty')
                    ->label('Reorder Qty')
                    ->numeric(decimalPlaces: 3),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
                TernaryFilter::make('below_reorder_point')
                    ->label('Below Reorder Point')
                    ->queries(
                        true: fn ($query) => $query->whereColumn('quantity', '<', 'reorder_point'),
                        false: fn ($query) => $query->whereColumn('quantity', '>=', 'reorder_point'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
