<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label(__('inventory.field.sku'))
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('name')
                    ->label(__('inventory.field.name'))
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->label(__('inventory.field.category'))
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('unit')
                    ->label(__('inventory.field.unit'))
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label(__('inventory.field.active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label(__('inventory.field.category'))
                    ->relationship('category', 'name'),
                TernaryFilter::make('is_active')
                    ->label(__('inventory.field.active')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
