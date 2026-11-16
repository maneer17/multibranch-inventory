<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Product;
use App\Services\ReorderPointService;
use App\Services\TransferOrderService;
use BackedEnum;
use BcMath\Number;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ReorderDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reorder-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('inventory.page.reorder_suggestions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('inventory.nav.transfers');
    }

    public function getTitle(): string
    {
        return __('inventory.page.reorder_suggestions');
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => app(ReorderPointService::class)->suggestAllTransfers())
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('inventory.field.product'))
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('to_branch.name')
                    ->label(__('inventory.reorder.needs_stock'))
                    ->icon(Heroicon::ArrowDown)
                    ->color('danger'),
                TextColumn::make('from_branch.name')
                    ->label(__('inventory.reorder.source'))
                    ->icon(Heroicon::ArrowUp)
                    ->color('success'),
                TextColumn::make('available')
                    ->label(__('inventory.reorder.current_stock'))
                    ->formatStateUsing(fn ($state) => $state->value)
                    ->color('danger')
                    ->description(__('inventory.reorder.below_reorder')),
                TextColumn::make('reorder_point')
                    ->label(__('inventory.field.reorder_point'))
                    ->formatStateUsing(fn ($state) => $state->value)
                    ->color('warning'),
                TextColumn::make('quantity')
                    ->label(__('inventory.reorder.suggested_qty'))
                    ->formatStateUsing(fn ($state) => $state->value)
                    ->badge()
                    ->color('primary'),
            ])
            ->recordActions([
                Action::make('createTransfer')
                    ->label(__('inventory.reorder.create_transfer'))
                    ->icon(Heroicon::PaperAirplane)
                    ->color('primary')
                    ->schema(fn (array $record) => [
                        Placeholder::make('summary')
                            ->label(__('inventory.reorder.transfer_summary'))
                            ->content(
                                "{$record['product']->name}: {$record['from_branch']->name} → {$record['to_branch']->name}"
                            ),
                        TextInput::make('quantity')
                            ->label(__('inventory.reorder.quantity_to_transfer'))
                            ->default(fn () => $record['quantity']->value)
                            ->numeric()
                            ->required()
                            ->minValue(1),
                    ])
                    ->action(function (array $data, array $record) {
                        app(TransferOrderService::class)->createRequest(
                            Branch::find($record['from_branch']->id),
                            Branch::find($record['to_branch']->id),
                            auth()->user(),
                            [
                                [
                                    'product' => Product::find($record['product']->id),
                                    'quantity' => new Number($data['quantity']),
                                ],
                            ],
                        );

                        Notification::make()
                            ->title(__('inventory.reorder.transfer_created'))
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__('inventory.reorder.well_stocked'))
            ->emptyStateDescription(__('inventory.reorder.well_stocked_desc'))
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
