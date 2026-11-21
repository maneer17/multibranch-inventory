<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        return __('inventory.dashboard.title');
    }

    /**
     * One line of context under the title, e.g. "Gaza branch · Branch manager",
     * so the user can see which data they are looking at.
     */
    public function getSubheading(): ?string
    {
        $user = auth()->user();

        $branch = $user->hasRole('admin')
            ? __('inventory.dashboard.all_branches')
            : ($user->branch?->name ?? '');

        $role = $user->getRoleNames()->first();

        return trim($branch.' · '.__('inventory.roles.'.$role), ' ·');
    }
}
