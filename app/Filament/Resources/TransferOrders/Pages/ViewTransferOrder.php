<?php

namespace App\Filament\Resources\TransferOrders\Pages;

use App\Enums\TransferStatus;
use App\Filament\Resources\TransferOrders\TransferOrderResource;
use App\Services\TransferOrderService;
use BcMath\Number;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewTransferOrder extends ViewRecord
{
    protected static string $resource = TransferOrderResource::class;

    public function getSubheading(): ?string
    {
        return __('inventory.subheading.transfer_order_view');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('inventory.transfer.details'))
                    ->icon(Heroicon::OutlinedTruck)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('fromBranch.name')
                                    ->label(__('inventory.field.from_branch'))
                                    ->icon(Heroicon::ArrowUpRight)
                                    ->color('warning'),
                                TextEntry::make('toBranch.name')
                                    ->label(__('inventory.field.to_branch'))
                                    ->icon(Heroicon::ArrowDownLeft)
                                    ->color('success'),
                                TextEntry::make('status')
                                    ->label(__('inventory.field.status'))
                                    ->badge(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('requester.name')->label(__('inventory.field.requested_by')),
                                TextEntry::make('approver.name')->label(__('inventory.field.approved_by'))->placeholder('—'),
                                TextEntry::make('created_at')->since()->label(__('inventory.transfer.requested'))
                                    ->helperText(fn ($record) => $record->created_at->format('M j, Y H:i')),
                            ]),
                    ]),

                Section::make(__('inventory.transfer.items'))
                    ->icon(Heroicon::OutlinedCube)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label(__('inventory.field.product'))
                                    ->weight('bold'),
                                TextEntry::make('quantity_requested')
                                    ->label(__('inventory.transfer.requested'))
                                    ->color('primary'),
                                TextEntry::make('quantity_sent')
                                    ->label(__('inventory.transfer.sent'))
                                    ->placeholder('—')
                                    ->color('warning'),
                                TextEntry::make('quantity_received')
                                    ->label(__('inventory.transfer.received'))
                                    ->placeholder('—')
                                    ->color('success'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('inventory.transfer.approve'))
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->visible(fn () => auth()->user()->can('approve', $this->record)
                    && $this->record->status->canTransitionTo(TransferStatus::Approved))
                ->action(function () {
                    app(TransferOrderService::class)->approve($this->record, auth()->user());
                    Notification::make()->title(__('inventory.transfer.approved'))->success()->send();
                }),

            Action::make('reject')
                ->label(__('inventory.transfer.reject'))
                ->icon(Heroicon::XCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => auth()->user()->can('reject', $this->record)
                    && $this->record->status->canTransitionTo(TransferStatus::Rejected))
                ->action(function () {
                    app(TransferOrderService::class)->reject($this->record);
                    Notification::make()->title(__('inventory.transfer.rejected'))->danger()->send();
                }),

            Action::make('cancel')
                ->label(__('inventory.transfer.cancel'))
                ->icon(Heroicon::NoSymbol)
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn () => auth()->user()->can('cancel', $this->record)
                    && $this->record->status->canTransitionTo(TransferStatus::Cancelled))
                ->action(function () {
                    app(TransferOrderService::class)->cancel($this->record);
                    Notification::make()->title(__('inventory.transfer.cancelled'))->warning()->send();
                }),

            Action::make('ship')
                ->label(__('inventory.transfer.ship'))
                ->icon(Heroicon::Truck)
                ->color('warning')
                ->visible(fn () => auth()->user()->can('ship', $this->record)
                    && $this->record->status->canTransitionTo(TransferStatus::InTransit))
                ->schema(fn () => $this->record->items->map(
                    fn ($item) => TextInput::make("sent.{$item->product_id}")
                        ->label($item->product->name)
                        ->default((string) $item->quantity_requested)
                        ->numeric()
                        ->required()
                )->all())
                ->action(function (array $data) {
                    $quantities = collect($data['sent'] ?? [])
                        ->mapWithKeys(fn ($qty, $productId) => [(int) $productId => new Number($qty)])
                        ->all();

                    app(TransferOrderService::class)->ship($this->record, auth()->user(), $quantities);
                    Notification::make()->title(__('inventory.transfer.shipped'))->success()->send();
                }),

            Action::make('receive')
                ->label(__('inventory.transfer.receive'))
                ->icon(Heroicon::InboxArrowDown)
                ->color('success')
                ->visible(fn () => auth()->user()->can('receive', $this->record)
                    && $this->record->status->canTransitionTo(TransferStatus::Received))
                ->schema(fn () => $this->record->items->map(
                    fn ($item) => TextInput::make("received.{$item->product_id}")
                        ->label($item->product->name)
                        ->default((string) $item->quantity_sent)
                        ->numeric()
                        ->required()
                )->all())
                ->action(function (array $data) {
                    $quantities = collect($data['received'] ?? [])
                        ->mapWithKeys(fn ($qty, $productId) => [(int) $productId => new Number($qty)])
                        ->all();

                    app(TransferOrderService::class)->receive($this->record, auth()->user(), $quantities);
                    Notification::make()->title(__('inventory.transfer.received_notification'))->success()->send();
                }),
        ];
    }
}
