<?php

namespace App\Filament\Widgets;

use App\Models\TimeEntry;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayEntriesWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()->isAdmin();
    }

    protected function getStats(): array
    {
        $hoy = TimeEntry::whereDate('datetime', today())->count();
        $entradas = TimeEntry::whereDate('datetime', today())
            ->where('type', 'entrada')
            ->count();

        return [
            Stat::make('Fichajes Hoy', $hoy)
                ->description($entradas . ' entradas registradas')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary')
                ->url(route('filament.admin.resources.time-entries.index')),
        ];
    }
}
