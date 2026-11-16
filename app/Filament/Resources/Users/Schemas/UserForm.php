<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('inventory.user.account'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('inventory.field.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('inventory.field.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->label(__('inventory.field.password'))
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->maxLength(255)
                            ->helperText(fn (string $operation): ?string =>
                                $operation === 'edit' ? __('inventory.user.password_hint') : null
                            ),
                    ])
                    ->columns(2),

                Section::make(__('inventory.user.access'))
                    ->schema([
                        Select::make('branch_id')
                            ->label(__('inventory.user.assigned_branch'))
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText(__('inventory.user.branch_hint')),

                        Select::make('roles')
                            ->label(__('inventory.user.role'))
                            ->relationship('roles', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText(__('inventory.user.role_hint')),
                    ])
                    ->columns(2),
            ]);
    }
}