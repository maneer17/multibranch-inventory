<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\MovementType;
use App\Filament\Concerns\HasBranchStockPivotFields;
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
use Filament\Tables\Table;

class BranchesRelationManager extends RelationManager
{
    use HasBranchStockPivotFields;

    protected static string $relationship = 'branches';

    public function form(Schema $schema): Schema
    {
        return $schema->components(self::pivotFormFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(self::pivotColumns(__('inventory.field.branch')))
            ->headerActions([
                AttachAction::make()
                    ->schema(fn (AttachAction $action): array => self::pivotAttachFields($action))
                    ->visible(fn () => auth()->user()->hasRole('admin')),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->hasRole('admin')),
                DetachAction::make()
                    ->visible(fn () => auth()->user()->hasRole('admin')),
                Action::make('recordInitialStock')
                    ->label(__('inventory.relation.record_initial_stock'))
                    ->schema([
                        TextInput::make('quantity')->required()->numeric(),
                    ])
                    ->visible(fn (Branch $record): bool => bccomp((string) $record->pivot->quantity, '0', 3) === 0)
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
                ])->visible(fn () => auth()->user()->hasRole('admin')),
            ]);
    }
}
