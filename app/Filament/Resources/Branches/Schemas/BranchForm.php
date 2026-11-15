<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label(__('inventory.field.code'))->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('name')->label(__('inventory.field.name'))->required()->maxLength(255),
                TextInput::make('city')->label(__('inventory.field.city'))->required()->maxLength(255),
                KeyValue::make('address')->label(__('inventory.field.address'))->required(),
                Toggle::make('is_active')->label(__('inventory.field.active'))->default(true),
            ]);
    }
}
