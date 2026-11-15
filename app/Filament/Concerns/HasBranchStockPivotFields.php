<?php

namespace App\Filament\Concerns;

use Filament\Actions\AttachAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

trait HasBranchStockPivotFields
{
    protected static function pivotFormFields(): array
    {
        return [
            TextInput::make('sale_price')->label(__('inventory.field.sale_price'))->required(),
            TextInput::make('purchase_price')->label(__('inventory.field.purchase_price'))->required(),
            TextInput::make('reorder_point')->label(__('inventory.field.reorder_point')),
            TextInput::make('reorder_qty')->label(__('inventory.field.reorder_qty')),
        ];
    }

    protected static function pivotAttachFields(AttachAction $action): array
    {
        return [
            $action->getRecordSelect(),
            ...self::pivotFormFields(),
        ];
    }

    protected static function pivotColumns(string $nameLabel): array
    {
        return [
            TextColumn::make('name')->label($nameLabel)->searchable(),
            TextColumn::make('pivot.quantity')->label(__('inventory.field.quantity')),
            TextColumn::make('pivot.sale_price')->label(__('inventory.field.sale_price')),
            TextColumn::make('pivot.purchase_price')->label(__('inventory.field.purchase_price')),
            TextColumn::make('pivot.reorder_point')->label(__('inventory.field.reorder_point')),
            TextColumn::make('pivot.reorder_qty')->label(__('inventory.field.reorder_qty')),
        ];
    }
}
