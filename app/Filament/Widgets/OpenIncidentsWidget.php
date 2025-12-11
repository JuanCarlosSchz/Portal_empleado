<?php

namespace App\Filament\Widgets;

use App\Models\Incident;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpenIncidentsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $query = Incident::query();
        
        // Si es trabajador, solo ver sus propias incidencias
        if (auth()->user()->isTrabajador()) {
            $query->where('user_id', auth()->id());
        }
        
        $pendientes = $query->clone()->where('status', 'pendiente')->count();
        $enRevision = $query->clone()->where('status', 'en_revision')->count();
        $total = $pendientes + $enRevision;

        $label = auth()->user()->isTrabajador() ? 'Mis Incidencias Abiertas' : 'Incidencias Abiertas';
        
        return [
            Stat::make($label, $total)
                ->description($pendientes . ' pendientes, ' . $enRevision . ' en revisión')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($total > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.incidents.index')),
        ];
    }
}
