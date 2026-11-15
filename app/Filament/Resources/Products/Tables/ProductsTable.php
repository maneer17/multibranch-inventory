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
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('unit')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Active'),
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
