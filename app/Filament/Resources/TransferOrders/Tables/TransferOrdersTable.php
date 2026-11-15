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
                    ->label('From')
                    ->icon(Heroicon::ArrowUpRight)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('toBranch.name')
                    ->label('To')
                    ->icon(Heroicon::ArrowDownLeft)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('requester.name')
                    ->label('Requested By'),
                TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->format('M j, Y')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TransferStatus::class)
                    ->multiple(),
                SelectFilter::make('from_branch_id')
                    ->label('From Branch')
                    ->relationship('fromBranch', 'name'),
                SelectFilter::make('to_branch_id')
                    ->label('To Branch')
                    ->relationship('toBranch', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
