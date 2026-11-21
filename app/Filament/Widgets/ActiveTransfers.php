<?php

namespace App\Filament\Widgets;

use App\Enums\TransferStatus;
use App\Filament\Resources\TransferOrders\TransferOrderResource;
use App\Models\TransferOrder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ActiveTransfers extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('inventory.dashboard.active_transfers'))
            // forUser() keeps non-admins to transfers that touch their own branch.
            ->query(fn () => TransferOrder::forUser(auth()->user())
                ->with(['fromBranch', 'toBranch'])
                ->whereIn('status', [
                    TransferStatus::Requested,
                    TransferStatus::Approved,
                    TransferStatus::InTransit,
                ])
                ->latest())
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->prefix('#'),
                TextColumn::make('fromBranch.name')
                    ->label(__('inventory.dashboard.from')),
                TextColumn::make('toBranch.name')
                    ->label(__('inventory.dashboard.to')),
                TextColumn::make('status')
                    ->label(__('inventory.dashboard.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('inventory.dashboard.requested'))
                    ->since(),
            ])
            ->paginated([5])
            ->recordUrl(fn (TransferOrder $record) => TransferOrderResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('inventory.dashboard.no_active_transfers'));
    }
}
