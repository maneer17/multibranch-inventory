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
                TextInput::make('code')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('city')->required()->maxLength(255),
                KeyValue::make('address')->required(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
