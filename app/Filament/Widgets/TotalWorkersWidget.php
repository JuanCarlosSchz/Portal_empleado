<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalWorkersWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()->isAdmin();
    }

    protected function getStats(): array
    {
        $trabajadores = User::where('role', 'trabajador')->count();
        $admins = User::where('role', 'admin')->count();

        return [
            Stat::make('Total Trabajadores', $trabajadores)
                ->description($admins . ' administradores en el sistema')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->url(route('filament.admin.resources.employees.index')),
        ];
    }
}
