<?php

namespace App\Filament\Resources\Branches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('inventory.field.code'))
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('name')
                    ->label(__('inventory.field.name'))
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('city')
                    ->label(__('inventory.field.city'))
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('inventory.field.active'))
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->hasRole('admin')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => auth()->user()->hasRole('admin')),
            ]);
    }
}
