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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.revenue-by-branch';

    public static function getNavigationLabel(): string
    {
        return __('inventory.page.revenue_by_branch');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('inventory.nav.reports');
    }

    public function getTitle(): string
    {
        return __('inventory.page.revenue_by_branch');
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                $user = auth()->user();
                $branchFilter = $user->hasRole('admin') ? '' : 'AND b.id = ?';
                $params = [MovementType::Sale->value];
                if (! $user->hasRole('admin')) {
                    $params[] = $user->branch_id;
                }

                return collect(DB::select("
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
                    WHERE 1=1 {$branchFilter}
                    GROUP BY b.id, b.name
                    ORDER BY total_revenue DESC
                ", $params))->map(fn ($row) => (array) $row);
            })
            ->columns([
                TextColumn::make('branch_name')
                    ->label(__('inventory.revenue.branch'))
                    ->weight('bold')
                    ->icon(Heroicon::BuildingStorefront),
                TextColumn::make('total_sales')
                    ->label(__('inventory.revenue.sales_count'))
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                TextColumn::make('total_revenue')
                    ->label(__('inventory.revenue.total_revenue'))
                    ->money('USD')
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->emptyStateHeading(__('inventory.revenue.no_sales'))
            ->emptyStateDescription(__('inventory.revenue.no_sales_desc'))
            ->emptyStateIcon(Heroicon::OutlinedBanknotes);
    }
}
