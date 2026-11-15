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
            TextInput::make('sale_price')->required(),
            TextInput::make('purchase_price')->required(),
            TextInput::make('reorder_point'),
            TextInput::make('reorder_qty'),
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
            TextColumn::make('pivot.quantity')->label('Quantity'),
            TextColumn::make('pivot.sale_price')->label('Sale Price'),
            TextColumn::make('pivot.purchase_price')->label('Purchase Price'),
            TextColumn::make('pivot.reorder_point')->label('Reorder Point'),
            TextColumn::make('pivot.reorder_qty')->label('Reorder Qty'),
        ];
    }
}
