<?php

namespace App\Filament\Resources\Branches\RelationManagers;

use App\Enums\MovementType;
use App\Filament\Concerns\HasBranchStockPivotFields;
use App\Models\Product;
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

class ProductsRelationManager extends RelationManager
{
    use HasBranchStockPivotFields;

    protected static string $relationship = 'products';

    public function form(Schema $schema): Schema
    {
        return $schema->components(self::pivotFormFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(self::pivotColumns(__('inventory.field.product')))
            ->headerActions([
                AttachAction::make()
                    ->schema(fn (AttachAction $action): array => self::pivotAttachFields($action)),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('recordInitialStock')
                    ->label(__('inventory.relation.record_initial_stock'))
                    ->schema([
                        TextInput::make('quantity')->required()->numeric(),
                    ])
                    ->visible(fn (Product $record): bool => bccomp((string) $record->pivot->quantity, '0', 3) === 0)
                    ->action(function (array $data, Product $record) {
                        app(StockMovementService::class)->submit(
                            product: $record,
                            branch: $this->getOwnerRecord(),
                            user: auth()->user(),
                            quantity: new Number($data['quantity']),
                            type: MovementType::InitialStockUpload,
                        );
                    }),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
