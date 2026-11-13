<?php

namespace App\Filament\Resources\StockMovements\Tables;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('branch.name')->label('Branch')->sortable()->searchable(),
                TextColumn::make('product.name')->label('Product')->sortable()->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('quantity')->label('Quantity')->sortable(),
                TextColumn::make('user.name')->label('Recorded By'),
                TextColumn::make('transferOrder.id')
                    ->label('Transfer')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : null)
                    ->placeholder('—'),
                TextColumn::make('note')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('branch.name'),
                        TextEntry::make('product.name'),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('quantity'),
                        TextEntry::make('user.name')->label('Recorded By'),
                        TextEntry::make('transferOrder.id')
                            ->formatStateUsing(fn ($state) => $state ? "#{$state}" : null)
                            ->placeholder('—'),
                        TextEntry::make('note')->placeholder('—'),
                    ]),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
