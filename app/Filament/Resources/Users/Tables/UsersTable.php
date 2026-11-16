<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('inventory.field.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('inventory.field.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('roles.name')
                    ->label(__('inventory.user.role'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin'          => 'danger',
                        'branch_manager' => 'warning',
                        'staff'          => 'gray',
                        default          => 'gray',
                    }),

                TextColumn::make('branch.name')
                    ->label(__('inventory.field.branch'))
                    ->placeholder(__('inventory.user.all_branches'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch')
                    ->relationship('branch', 'name'),

                SelectFilter::make('role')
                    ->relationship('roles', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (User $record) {
                        if ($record->is(auth()->user())) {
                            throw new \Exception('You cannot delete your own account.');
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}