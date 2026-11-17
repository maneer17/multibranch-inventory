<?php

namespace App\Filament\Resources\StockMovements\Actions;

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Services\StockMovementService;
use BcMath\Number;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

class RecordMovementAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'recordMovement';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventory.movement.record'))
            ->icon(Heroicon::Plus)
            ->visible(fn () => self::userCanRecord())
            ->modalWidth('lg')
            ->schema([
                Select::make('branch_id')
                    ->label(__('inventory.field.branch'))
                    ->options(fn () => self::branchOptions())
                    ->default(fn () => auth()->user()->branch_id)
                    ->disabled(fn () => ! auth()->user()->hasRole('admin'))
                    ->dehydrated()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $set('product_id', null);
                        $set('unit_price', null);
                    }),

                Select::make('product_id')
                    ->label(__('inventory.field.product'))
                    ->options(fn (Get $get) => self::productOptions($get('branch_id')))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::fillPrice($get, $set)),

                Select::make('type')
                    ->label(__('inventory.field.type'))
                    ->options(fn () => self::typeOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::fillPrice($get, $set)),

                TextInput::make('quantity')
                    ->label(__('inventory.field.quantity'))
                    ->numeric()
                    ->required()
                    ->minValue(0.001),

                TextInput::make('unit_price')
                    ->label(__('inventory.field.unit_price'))
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (Get $get) => self::isPricedType($get('type')))
                    ->required(fn (Get $get) => self::isPricedType($get('type'))),

                Textarea::make('note')
                    ->label(__('inventory.field.note'))
                    ->rows(2)
                    ->maxLength(500)
                    ->required(fn (Get $get) => self::isAdjustment($get('type'))),
            ])
            ->action(fn (array $data) => $this->submitMovement($data));
    }

    private function submitMovement(array $data): void
    {
        $user = auth()->user();

        // Never trust the submitted branch for non-admins: use their own.
        $branchId = $user->hasRole('admin') ? $data['branch_id'] : $user->branch_id;
        abort_unless($branchId, 403);

        // Only manually recordable types, even if the form was tampered with.
        $type = MovementType::from($data['type']);
        abort_unless($type->isManual(), 403);

        try {
            app(StockMovementService::class)->submit(
                Product::findOrFail($data['product_id']),
                Branch::findOrFail($branchId),
                $user,
                new Number((string) $data['quantity']),
                $type,
                $data['note'] ?? null,
                null,
                filled($data['unit_price'] ?? null) ? new Number((string) $data['unit_price']) : null,
            );
        } catch (\DomainException | \InvalidArgumentException $e) {
            Notification::make()
                ->title(__('inventory.movement.failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt(); // keep the modal open so the user can correct the input
        }

        Notification::make()
            ->title(__('inventory.movement.recorded'))
            ->success()
            ->send();
    }

    /** Admins, or anyone assigned to a branch. Fails closed otherwise. */
    private static function userCanRecord(): bool
    {
        $user = auth()->user();

        return $user->hasRole('admin') || $user->branch_id !== null;
    }

    private static function branchOptions(): array
    {
        $user = auth()->user();

        return Branch::query()
            ->when(! $user->hasRole('admin'), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** Only products the branch already carries; the service needs that row. */
    private static function productOptions(mixed $branchId): array
    {
        if (blank($branchId)) {
            return [];
        }

        return BranchStock::query()
            ->where('branch_id', $branchId)
            ->with('product:id,name')
            ->get()
            ->mapWithKeys(fn (BranchStock $stock) => [$stock->product_id => $stock->product->name])
            ->sortBy(fn ($name) => $name)
            ->all();
    }

    private static function typeOptions(): array
    {
        return collect(MovementType::cases())
            ->filter(fn (MovementType $type) => $type->isManual())
            ->mapWithKeys(fn (MovementType $type) => [$type->value => $type->getLabel()])
            ->all();
    }

    /** Default the unit price from the branch's own price for the product. */
    private static function fillPrice(Get $get, Set $set): void
    {
        $type = MovementType::tryFrom((string) $get('type'));

        if (! $type || blank($get('branch_id')) || blank($get('product_id'))) {
            return;
        }

        $stock = BranchStock::query()
            ->where('branch_id', $get('branch_id'))
            ->where('product_id', $get('product_id'))
            ->first();

        $price = match ($type) {
            MovementType::Sale => $stock?->sale_price,
            MovementType::PurchaseReceipt => $stock?->purchase_price,
            default => null,
        };

        $set('unit_price', $price?->value);
    }

    private static function isPricedType(mixed $type): bool
    {
        return in_array($type, [MovementType::Sale->value, MovementType::PurchaseReceipt->value], true);
    }

    private static function isAdjustment(mixed $type): bool
    {
        return in_array($type, [MovementType::AdjustmentIn->value, MovementType::AdjustmentOut->value], true);
    }
}
