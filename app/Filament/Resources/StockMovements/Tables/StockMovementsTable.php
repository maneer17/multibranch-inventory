<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Enums\MovementType;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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
                    ->since()
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->format('M j, Y H:i')),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->weight('bold')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable()
                    ->color(fn ($state) => $state->compare(0) < 0 ? 'danger' : 'success'),
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label('Recorded By'),
                TextColumn::make('transferOrder.id')
                    ->label('Transfer')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : null)
                    ->placeholder('—')
                    ->url(fn ($record) => $record->transfer_order_id
                        ? \App\Filament\Resources\TransferOrders\TransferOrderResource::getUrl('view', ['record' => $record->transfer_order_id])
                        : null)
                    ->color('primary'),
                TextColumn::make('note')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(MovementType::class)
                    ->multiple(),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
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
