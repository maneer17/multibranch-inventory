<?php

namespace App\Filament\Pages;

use App\Enums\MovementType;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class RevenueByBranch extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Revenue by Branch';
    protected static ?string $title = 'Revenue by Branch';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string|\UnitEnum|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.revenue-by-branch';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => collect(DB::select("
                SELECT
                    b.id AS branch_id,
                    b.name AS branch_name,
                    COUNT(sm.id) AS total_sales,
                    COALESCE(SUM(ABS(sm.quantity) * sm.unit_price), 0) AS total_revenue
                FROM branches b
                LEFT JOIN stock_movements sm
                    ON sm.branch_id = b.id
                    AND sm.type = ?
                    AND sm.unit_price IS NOT NULL
                GROUP BY b.id, b.name
                ORDER BY total_revenue DESC
            ", [MovementType::Sale->value]))->map(fn ($row) => (array) $row))
            ->columns([
                TextColumn::make('branch_name')
                    ->label('Branch')
                    ->weight('bold')
                    ->icon(Heroicon::BuildingStorefront),
                TextColumn::make('total_sales')
                    ->label('Sales Count')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->money('USD')
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->emptyStateHeading('No sales recorded yet')
            ->emptyStateDescription('Revenue data will appear here once sales movements are logged.')
            ->emptyStateIcon(Heroicon::OutlinedBanknotes);
    }
}
