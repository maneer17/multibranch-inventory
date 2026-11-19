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
                    ->label(__('inventory.field.branch'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label(__('inventory.field.product'))
                    ->weight('bold')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label(__('inventory.field.quantity'))
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->color(fn ($record) => $record->quantity->compare($record->reorder_point) < 0 ? 'danger' : null),
                TextColumn::make('sale_price')
                    ->label(__('inventory.field.sale_price'))
                    ->money('USD'),
                TextColumn::make('purchase_price')
                    ->label(__('inventory.field.purchase_price'))
                    ->money('USD'),
                TextColumn::make('reorder_point')
                    ->label(__('inventory.field.reorder_point'))
                    ->numeric(decimalPlaces: 3)
                    ->color('warning'),
                TextColumn::make('reorder_qty')
                    ->label(__('inventory.field.reorder_qty'))
                    ->numeric(decimalPlaces: 3),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('inventory.field.branch'))
                    ->relationship('branch', 'name'),
                TernaryFilter::make('below_reorder_point')
                    ->label(__('inventory.field.below_reorder_point'))
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
