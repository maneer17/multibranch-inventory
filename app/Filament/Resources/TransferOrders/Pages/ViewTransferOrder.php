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
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewTransferOrder extends ViewRecord
{
    protected static string $resource = TransferOrderResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transfer Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('fromBranch.name')->label('From Branch'),
                                TextEntry::make('toBranch.name')->label('To Branch'),
                                TextEntry::make('requester.name')->label('Requested By'),
                                TextEntry::make('approver.name')->label('Approved By')->placeholder('—'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('created_at')->dateTime()->label('Requested At'),
                            ]),
                    ]),

                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('product.name')->label('Product'),
                                TextEntry::make('quantity_requested')->label('Requested'),
                                TextEntry::make('quantity_sent')->label('Sent')->placeholder('—'),
                                TextEntry::make('quantity_received')->label('Received')->placeholder('—'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->visible(fn () => auth()->user()->can('approve', $this->record)
                    && $this->record->status->canTransitionTo(TransferStatus::Approved))
                ->action(fn () => app(TransferOrderService::class)
                    ->approve($this->record, auth()->user())),

            Action::make('reject')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => auth()->user()->can('reject', $this->record)
                    && $this->record->status->canTransitionTo(TransferStatus::Rejected))
                ->action(fn () => app(TransferOrderService::class)
                    ->reject($this->record)),

            Action::make('cancel')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => auth()->user()->can('cancel', $this->record)
                    && $this->record->status->canTransitionTo(TransferStatus::Cancelled))
                ->action(fn () => app(TransferOrderService::class)
                    ->cancel($this->record)),

            Action::make('ship')
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
                }),

            Action::make('receive')
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
                }),
        ];
    }
}
