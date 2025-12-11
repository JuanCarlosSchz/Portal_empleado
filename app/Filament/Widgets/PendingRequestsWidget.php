<?php

namespace App\Filament\Widgets;

use App\Models\Request;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingRequestsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $query = Request::where('status', 'pendiente');
        
        // Si es trabajador, solo ver sus propias solicitudes
        if (auth()->user()->isTrabajador()) {
            $query->where('user_id', auth()->id());
        }
        
        $pendientes = $query->count();

        $label = auth()->user()->isTrabajador() ? 'Mis Solicitudes Pendientes' : 'Solicitudes Pendientes';
        $description = auth()->user()->isTrabajador() 
            ? 'Esperando respuesta del administrador' 
            : 'Esperando respuesta del administrador';

        return [
            Stat::make($label, $pendientes)
                ->description($description)
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendientes > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.requests.index')),
        ];
    }
}
