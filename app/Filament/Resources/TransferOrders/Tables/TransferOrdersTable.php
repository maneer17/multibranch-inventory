<?php

namespace App\Filament\Resources\TransferOrders\Tables;

use App\Enums\TransferStatus;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransferOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('fromBranch.name')
                    ->label(__('inventory.field.from_branch'))
                    ->icon(Heroicon::ArrowUpRight)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('toBranch.name')
                    ->label(__('inventory.field.to_branch'))
                    ->icon(Heroicon::ArrowDownLeft)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('requester.name')
                    ->label(__('inventory.field.requested_by')),
                TextColumn::make('approver.name')
                    ->label(__('inventory.field.approved_by'))
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('inventory.field.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('inventory.field.created'))
                    ->since()
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->format('M j, Y')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('inventory.field.status'))
                    ->options(TransferStatus::class)
                    ->multiple(),
                SelectFilter::make('from_branch_id')
                    ->label(__('inventory.field.from_branch'))
                    ->relationship('fromBranch', 'name'),
                SelectFilter::make('to_branch_id')
                    ->label(__('inventory.field.to_branch'))
                    ->relationship('toBranch', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
