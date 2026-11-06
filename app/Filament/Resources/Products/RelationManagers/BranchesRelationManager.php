<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\MovementType;
use App\Models\Branch;
use App\Services\StockMovementService;
use BcMath\Number;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sale_price')->required(),
                TextInput::make('purchase_price')->required(),
                TextInput::make('reorder_point'),
                TextInput::make('reorder_qty'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Branch')->searchable(),
                TextColumn::make('pivot.quantity')->label('Quantity'),
                TextColumn::make('pivot.sale_price')->label('Sale Price'),
                TextColumn::make('pivot.purchase_price')->label('Purchase Price'),
                TextColumn::make('pivot.reorder_point')->label('Reorder Point'),
                TextColumn::make('pivot.reorder_qty')->label('Reorder Qty'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('sale_price')->required(),
                        TextInput::make('purchase_price')->required(),
                        TextInput::make('reorder_point'),
                        TextInput::make('reorder_qty'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
                Action::make('recordInitialStock')
                    ->label('Record Initial Stock')
                    ->schema([
                        TextInput::make('quantity')->required()->numeric(),
                    ])
                    ->visible(fn (Branch $record): bool => bccomp((string) $record->pivot->quantity, '0', 3) === 0
                    )
                    ->action(function (array $data, Branch $record) {
                        app(StockMovementService::class)->submit(
                            product: $this->getOwnerRecord(),
                            branch: $record,
                            user: auth()->user(),
                            quantity: new Number($data['quantity']),
                            type: MovementType::InitialStockUpload,
                        );
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
