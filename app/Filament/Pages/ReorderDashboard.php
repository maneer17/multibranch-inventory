<?php

namespace App\Filament\Pages;

use App\Services\ReorderPointService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ReorderDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Reorder Suggestions';
    protected static ?string $title = 'Reorder Suggestions';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected string $view = 'filament.pages.reorder-dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => app(ReorderPointService::class)->suggestAllTransfers())
            ->columns([
                TextColumn::make('product.name')->label('Product'),
                TextColumn::make('to_branch.name')->label('Needs Stock'),
                TextColumn::make('from_branch.name')->label('Source'),
                TextColumn::make('available')
                    ->label('Current Stock')
                    ->formatStateUsing(fn ($state) => $state->value),
                TextColumn::make('reorder_point')
                    ->label('Reorder Point')
                    ->formatStateUsing(fn ($state) => $state->value),
                TextColumn::make('quantity')
                    ->label('Suggested Transfer')
                    ->formatStateUsing(fn ($state) => $state->value)
                    ->weight('bold'),
            ]);
    }
}
