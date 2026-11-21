<?php

namespace App\Filament\Widgets;

use App\Enums\TransferStatus;
use App\Filament\Pages\ReorderDashboard;
use App\Filament\Resources\TransferOrders\TransferOrderResource;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use App\Models\User;
use App\Services\ReorderPointService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class BranchStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Filament refreshes stats every 5 seconds by default. Once a minute is plenty here.
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        $stats = [
            $this->reorderStat($user, $isAdmin),
            $this->receivingStat($user, $isAdmin),
        ];

        // Only people who can approve transfers see this card.
        if ($user->hasAnyRole(['admin', 'branch_manager'])) {
            $stats[] = $this->approvalsStat($user, $isAdmin);
        }

        $stats[] = $this->movementsStat($user);

        return $stats;
    }

    /**
     * Open reorder suggestions. Calculating them loads all stock, so the
     * count is cached for 5 minutes (per branch, or one shared key for admin).
     */
    private function reorderStat(User $user, bool $isAdmin): Stat
    {
        $count = Cache::remember(
            'dashboard.reorder-count.'.($isAdmin ? 'all' : $user->branch_id),
            300,
            fn () => app(ReorderPointService::class)
                ->suggestAllTransfers()
                ->when(! $isAdmin, fn ($suggestions) => $suggestions->filter(
                    fn (array $s) => $s['to_branch']->id === $user->branch_id
                        || $s['from_branch']->id === $user->branch_id
                ))
                ->count(),
        );

        return Stat::make(__('inventory.dashboard.reorder'), $count)
            ->description(__('inventory.dashboard.reorder_hint'))
            ->descriptionIcon('heroicon-m-arrow-path')
            ->color($count > 0 ? 'warning' : 'success')
            ->url(ReorderDashboard::getUrl());
    }

    /**
     * Transfers that have been shipped to this branch and are waiting to be received.
     */
    private function receivingStat(User $user, bool $isAdmin): Stat
    {
        $count = TransferOrder::forUser($user)
            ->where('status', TransferStatus::InTransit)
            ->when(! $isAdmin, fn ($query) => $query->where('to_branch_id', $user->branch_id))
            ->count();

        return Stat::make(__('inventory.dashboard.to_receive'), $count)
            ->description(__('inventory.dashboard.to_receive_hint'))
            ->descriptionIcon('heroicon-m-truck')
            ->color($count > 0 ? 'info' : 'gray')
            ->url(TransferOrderResource::getUrl());
    }

    /**
     * Requested transfers this user is allowed to approve: from their own branch,
     * and not requested by themselves (same rule as TransferOrderPolicy::approve).
     */
    private function approvalsStat(User $user, bool $isAdmin): Stat
    {
        $count = TransferOrder::forUser($user)
            ->where('status', TransferStatus::Requested)
            ->where('requested_by', '!=', $user->id)
            ->when(! $isAdmin, fn ($query) => $query->where('from_branch_id', $user->branch_id))
            ->count();

        return Stat::make(__('inventory.dashboard.to_approve'), $count)
            ->description(__('inventory.dashboard.to_approve_hint'))
            ->descriptionIcon('heroicon-m-check-badge')
            ->color($count > 0 ? 'warning' : 'gray')
            ->url(TransferOrderResource::getUrl());
    }

    /**
     * Movements recorded in the last 7 days, with a small per-day sparkline.
     */
    private function movementsStat(User $user): Stat
    {
        $perDay = StockMovement::forUser($user)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        // One value per day, oldest first, with 0 for days that have no movements.
        $chart = collect(range(6, 0))
            ->map(fn (int $daysAgo) => (int) ($perDay[now()->subDays($daysAgo)->toDateString()] ?? 0))
            ->all();

        return Stat::make(__('inventory.dashboard.movements'), array_sum($chart))
            ->description(__('inventory.dashboard.last_7_days'))
            ->descriptionIcon('heroicon-m-arrows-right-left')
            ->chart($chart)
            ->color('primary');
    }
}
