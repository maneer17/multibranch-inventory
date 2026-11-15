<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Product;
use App\Services\ReorderPointService;
use App\Services\TransferOrderService;
use BackedEnum;
use BcMath\Number;
use Filament\Actions\Action as PageAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ReorderDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Reorder Suggestions';
    protected static ?string $title = 'Reorder Suggestions';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;
    protected static string|\UnitEnum|null $navigationGroup = 'Transfers';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reorder-dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => app(ReorderPointService::class)->suggestAllTransfers())
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('to_branch.name')
                    ->label('Needs Stock')
                    ->icon(Heroicon::ArrowDown)
                    ->color('danger'),
                TextColumn::make('from_branch.name')
                    ->label('Source')
                    ->icon(Heroicon::ArrowUp)
                    ->color('success'),
                TextColumn::make('available')
                    ->label('Current Stock')
                    ->formatStateUsing(fn ($state) => $state->value)
                    ->color('danger')
                    ->description('Below reorder point'),
                TextColumn::make('reorder_point')
                    ->label('Reorder Point')
                    ->formatStateUsing(fn ($state) => $state->value)
                    ->color('warning'),
                TextColumn::make('quantity')
                    ->label('Suggested Qty')
                    ->formatStateUsing(fn ($state) => $state->value)
                    ->badge()
                    ->color('primary'),
            ])
            ->recordActions([
                Action::make('createTransfer')
                    ->label('Create Transfer')
                    ->icon(Heroicon::PaperAirplane)
                    ->color('primary')
                    ->schema(fn (array $record) => [
                        Placeholder::make('summary')
                            ->label('Transfer Summary')
                            ->content(
                                "{$record['product']->name}: {$record['from_branch']->name} → {$record['to_branch']->name}"
                            ),
                        TextInput::make('quantity')
                            ->label('Quantity to transfer')
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
                            ->title('Transfer request created')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('All branches are well stocked')
            ->emptyStateDescription('No products are below their reorder point right now.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
