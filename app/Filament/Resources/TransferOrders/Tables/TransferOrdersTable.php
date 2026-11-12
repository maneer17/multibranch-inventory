<?php

namespace App\Filament\Resources\TransferOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransferOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //protected $fillable = ['from_branch_id', 'to_branch_id', 'requested_by', 'approved_by', 'status'];

                TextColumn::make('fromBranch.name')->label('From Branch'),
                TextColumn::make('toBranch.name')->label('To Branch'),
                TextColumn::make('requester.name')->label('Requested By'),
                TextColumn::make('approver.name')->label('Approved By'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Created At'),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([

                    ]),
               // EditAction::make(),
            ])
            ->toolbarActions([
            ]);
    }
}
