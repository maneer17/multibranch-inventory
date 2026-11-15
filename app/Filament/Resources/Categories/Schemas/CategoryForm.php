<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(__('inventory.field.name'))->required()->maxLength(255),
                TextInput::make('code')->label(__('inventory.field.code'))->required()->maxLength(255)->unique(ignoreRecord: true),
            ]);
    }
}
