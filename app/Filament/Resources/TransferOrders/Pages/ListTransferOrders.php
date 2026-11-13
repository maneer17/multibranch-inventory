<?php

namespace App\Filament\Resources\TransferOrders\Pages;

use App\Filament\Resources\TransferOrders\TransferOrderResource;
use App\Models\Branch;
use App\Models\Product;
use App\Services\TransferOrderService;
use BcMath\Number;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

class ListTransferOrders extends ListRecords
{
    protected static string $resource = TransferOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createRequest')
                ->label('New Transfer Request')
                ->visible(fn () => auth()->user()->hasAnyRole(['admin', 'branch_manager', 'staff']))
                ->schema([
                    Select::make('from_branch_id')
                        ->label('From Branch')
                        ->options(fn () => auth()->user()->hasRole('admin')
                            ? Branch::pluck('name', 'id')
                            : Branch::where('id', auth()->user()->branch_id)->pluck('name', 'id'))
                        ->default(fn () => auth()->user()->branch_id)
                        ->disabled(fn () => ! auth()->user()->hasRole('admin'))
                        // Disabled fields are stripped from submitted data by
                        // default — this one's value IS the request's source
                        // branch, not decoration, so it must still submit.
                        ->dehydrated(true)
                        ->required()
                        ->live(),

                    Select::make('to_branch_id')
                        ->label('To Branch')
                        ->options(fn (Get $get) => Branch::where('id', '!=', $get('from_branch_id'))
                            ->pluck('name', 'id'))
                        ->required(),

                    Repeater::make('items')
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->options(Product::pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                            TextInput::make('quantity')
                                ->required()
                                ->numeric(),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $from = Branch::findOrFail($data['from_branch_id']);
                    $to = Branch::findOrFail($data['to_branch_id']);

                    $items = collect($data['items'])->map(fn ($item) => [
                        'product' => Product::findOrFail($item['product_id']),
                        'quantity' => new Number($item['quantity']),
                    ])->all();

                    app(TransferOrderService::class)->createRequest(
                        $from,
                        $to,
                        auth()->user(),
                        $items,
                    );
                }),
        ];
    }
}
