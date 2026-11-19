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
                    ->label(__('inventory.field.date'))
                    ->since()
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->format('M j, Y H:i')),
                TextColumn::make('branch.name')
                    ->label(__('inventory.field.branch'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label(__('inventory.field.product'))
                    ->weight('bold')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('inventory.field.type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label(__('inventory.field.quantity'))
                    ->sortable()
                    ->color(fn ($state) => $state->compare(0) < 0 ? 'danger' : 'success'),
                TextColumn::make('unit_price')
                    ->label(__('inventory.field.unit_price'))
                    ->money('USD')
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label(__('inventory.field.recorded_by')),
                TextColumn::make('transferOrder.id')
                    ->label(__('inventory.field.transfer'))
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : null)
                    ->placeholder('—')
                    ->url(fn ($record) => $record->transfer_order_id
                        ? \App\Filament\Resources\TransferOrders\TransferOrderResource::getUrl('view', ['record' => $record->transfer_order_id])
                        : null)
                    ->color('primary'),
                TextColumn::make('note')
                    ->label(__('inventory.field.note'))
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('inventory.field.type'))
                    ->options(MovementType::class)
                    ->multiple(),
                SelectFilter::make('branch_id')
                    ->label(__('inventory.field.branch'))
                    ->relationship('branch', 'name'),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label(__('inventory.field.from')),
                        \Filament\Forms\Components\DatePicker::make('until')->label(__('inventory.field.until')),
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
                        TextEntry::make('branch.name')->label(__('inventory.field.branch')),
                        TextEntry::make('product.name')->label(__('inventory.field.product')),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('quantity'),
                        TextEntry::make('user.name')->label(__('inventory.field.recorded_by')),
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
