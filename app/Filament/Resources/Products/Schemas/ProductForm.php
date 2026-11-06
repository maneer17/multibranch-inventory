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
                TextInput::make('name')->required()->maxLength(255),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable(),
                Select::make('unit')
                    ->options([
                        'kg' => 'Kilograms',
                        'pcs' => 'Pieces',
                        'liters' => 'Liters',
                        'meters' => 'Meters',
                    ])
                    ->required(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
