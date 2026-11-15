<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(__('inventory.field.name'))->required()->maxLength(255),
                Select::make('category_id')
                    ->label(__('inventory.field.category'))
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable(),
                Select::make('unit')
                    ->label(__('inventory.field.unit'))
                    ->options([
                        'kg' => __('inventory.unit.kg'),
                        'pcs' => __('inventory.unit.pcs'),
                        'liters' => __('inventory.unit.liters'),
                        'meters' => __('inventory.unit.meters'),
                    ])
                    ->required(),
                Toggle::make('is_active')->label(__('inventory.field.active'))->default(true),
            ]);
    }
}
