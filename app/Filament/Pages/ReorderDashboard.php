<?php

namespace App\Filament\Pages;

use App\Services\ReorderPointService;
use App\Services\TransferOrderService;
use BackedEnum;
use BcMath\Number;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

class ReorderDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reorder-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['admin', 'branch_manager']);
    }

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

    public function getSubheading(): ?string
    {
        return __('inventory.subheading.reorder_suggestions');
    }

    /**
     * Suggestions visible to the current user, computed once per request.
     *
     * Rows are keyed by "to:from:product" so a row keeps the same key even if
     * the list changes between opening an action and submitting it.
     */
    #[Computed]
    public function suggestions(): array
    {
        return collect(app(ReorderPointService::class)->suggestAllTransfers())
            ->filter(fn (array $suggestion) => $this->canRequestFor($suggestion))
            ->keyBy(fn (array $s) => "{$s['to_branch']->id}:{$s['from_branch']->id}:{$s['product']->id}")
            ->all();
    }

    /**
     * Admins can act on any suggestion. A branch manager can only request
     * stock for their own branch (the one that is running low).
     */
    private function canRequestFor(array $suggestion): bool
    {
        $user = auth()->user();

        return $user->hasRole('admin')
            || (int) $suggestion['to_branch']->id === (int) $user->branch_id;
    }

    /**
     * Turn the selected suggestions into transfer orders: one order per
     * (source → destination) pair, with every product for that pair as an item.
     */
    private function createGroupedTransfers(Collection $records): void
    {
        $user = auth()->user();

        // Check every selected row again on the server; the checkbox is not authorization.
        abort_unless($records->every(fn (array $r) => $this->canRequestFor($r)), 403);

        $created = 0;
        $failures = [];

        $records
            ->groupBy(fn (array $r) => "{$r['from_branch']->id}:{$r['to_branch']->id}")
            ->each(function (Collection $group) use ($user, &$created, &$failures) {
                $first = $group->first();

                $items = $group
                    ->map(fn (array $r) => [
                        'product' => $r['product'],
                        'quantity' => $r['quantity'],
                    ])
                    ->values()
                    ->all();

                try {
                    app(TransferOrderService::class)->createRequest(
                        $first['from_branch'],
                        $first['to_branch'],
                        $user,
                        $items,
                    );

                    $created++;
                } catch (\DomainException | \InvalidArgumentException $e) {
                    $failures[] = "{$first['from_branch']->name} → {$first['to_branch']->name}: {$e->getMessage()}";
                }
            });

        if ($created > 0) {
            Notification::make()
                ->title(__('inventory.reorder.transfers_created', ['count' => $created]))
                ->success()
                ->send();
        }

        if ($failures !== []) {
            Notification::make()
                ->title(__('inventory.reorder.transfers_failed', ['count' => count($failures)]))
                ->body(implode("\n", $failures))
                ->danger()
                ->persistent()
                ->send();
        }

        unset($this->suggestions);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search) {
                return collect($this->suggestions)
                    ->when(filled($search), fn ($rows) => $rows->filter(
                        fn (array $s) => Str::contains($s['product']->name, $search, ignoreCase: true)
                    ))
                    ->all();
            })
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
                    ->visible(fn (array $record) => $this->canRequestFor($record))
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
                        // Hiding the button is not authorization; check again on submit.
                        abort_unless($this->canRequestFor($record), 403);

                        app(TransferOrderService::class)->createRequest(
                            $record['from_branch'],
                            $record['to_branch'],
                            auth()->user(),
                            [
                                [
                                    'product' => $record['product'],
                                    'quantity' => new Number((string) $data['quantity']),
                                ],
                            ],
                        );

                        unset($this->suggestions);

                        Notification::make()
                            ->title(__('inventory.reorder.transfer_created'))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('createGroupedTransfers')
                        ->label(__('inventory.reorder.create_selected'))
                        ->icon(Heroicon::PaperAirplane)
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalDescription(__('inventory.reorder.create_selected_confirm'))
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $this->createGroupedTransfers($records)),
                ]),
            ])
            ->emptyStateHeading(__('inventory.reorder.well_stocked'))
            ->emptyStateDescription(__('inventory.reorder.well_stocked_desc'))
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
