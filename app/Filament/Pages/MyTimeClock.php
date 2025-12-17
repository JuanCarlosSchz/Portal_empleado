<?php

namespace App\Filament\Pages;

use App\Models\TimeEntry;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class MyTimeClock extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.my-time-clock';
    
    protected static ?string $navigationLabel = 'Mi Fichaje';
    
    protected static ?string $title = 'Mi Fichaje';
    
    protected static ?int $navigationSort = 1;
    
    public static function canAccess(): bool
    {
        return auth()->user()->isTrabajador();
    }
    
    public function getViewData(): array
    {
        $user = auth()->user();
        
        // Obtener registros agrupados por día de los últimos 30 días
        $entries = TimeEntry::where('user_id', $user->id)
            ->where('datetime', '>=', now()->subDays(30))
            ->orderBy('datetime', 'desc')
            ->get()
            ->groupBy(function($entry) {
                return $entry->datetime->format('Y-m-d');
            });
        
        // Calcular horas por día
        $dailyData = [];
        foreach ($entries as $date => $dayEntries) {
            $entrada = $dayEntries->where('type', 'entrada')->first();
            $salida = $dayEntries->where('type', 'salida')->first();
            
            $totalMinutes = 0;
            $pauseMinutes = 0;
            
            if ($entrada && $salida) {
                $totalMinutes = $entrada->datetime->diffInMinutes($salida->datetime);
                
                // Calcular pausas
                $pausas = $dayEntries->whereIn('type', ['pausa_inicio', 'pausa_fin'])->sortBy('datetime');
                $pausaInicio = null;
                foreach ($pausas as $pausa) {
                    if ($pausa->type === 'pausa_inicio') {
                        $pausaInicio = $pausa->datetime;
                    } elseif ($pausa->type === 'pausa_fin' && $pausaInicio) {
                        $pauseMinutes += $pausaInicio->diffInMinutes($pausa->datetime);
                        $pausaInicio = null;
                    }
                }
            }
            
            $workMinutes = $totalMinutes - $pauseMinutes;
            
            $dailyData[$date] = [
                'date' => Carbon::parse($date),
                'entries' => $dayEntries->sortBy('datetime'),
                'entrada' => $entrada,
                'salida' => $salida,
                'total_hours' => floor($workMinutes / 60) . 'h ' . ($workMinutes % 60) . 'm',
                'pause_hours' => floor($pauseMinutes / 60) . 'h ' . ($pauseMinutes % 60) . 'm',
                'has_open_shift' => $entrada && !$salida,
            ];
        }
        
        return [
            'dailyData' => $dailyData,
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('clock_in')
                ->label('Iniciar Jornada')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => !$this->hasOpenShift())
                ->action(function () {
                    TimeEntry::create([
                        'user_id' => auth()->id(),
                        'type' => 'entrada',
                        'datetime' => now(),
                    ]);
                    
                    Notification::make()
                        ->title('Jornada iniciada')
                        ->success()
                        ->send();
                        
                    $this->redirect(static::getUrl());
                }),
                
            Action::make('start_pause')
                ->label('Iniciar Pausa')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->visible(fn () => $this->hasOpenShift() && !$this->hasOpenPause())
                ->action(function () {
                    TimeEntry::create([
                        'user_id' => auth()->id(),
                        'type' => 'pausa_inicio',
                        'datetime' => now(),
                    ]);
                    
                    Notification::make()
                        ->title('Pausa iniciada')
                        ->success()
                        ->send();
                        
                    $this->redirect(static::getUrl());
                }),
                
            Action::make('end_pause')
                ->label('Reanudar Jornada')
                ->icon('heroicon-o-play')
                ->color('info')
                ->visible(fn () => $this->hasOpenPause())
                ->action(function () {
                    TimeEntry::create([
                        'user_id' => auth()->id(),
                        'type' => 'pausa_fin',
                        'datetime' => now(),
                    ]);
                    
                    Notification::make()
                        ->title('Jornada reanudada')
                        ->success()
                        ->send();
                        
                    $this->redirect(static::getUrl());
                }),
                
            Action::make('clock_out')
                ->label('Fin de Jornada')
                ->icon('heroicon-o-stop')
                ->color('danger')
                ->visible(fn () => $this->hasOpenShift() && !$this->hasOpenPause())
                ->action(function () {
                    TimeEntry::create([
                        'user_id' => auth()->id(),
                        'type' => 'salida',
                        'datetime' => now(),
                    ]);
                    
                    Notification::make()
                        ->title('Jornada finalizada')
                        ->success()
                        ->send();
                        
                    $this->redirect(static::getUrl());
                }),
        ];
    }
    
    protected function hasOpenShift(): bool
    {
        $today = today();
        
        // Obtener la última entrada del día
        $ultimaEntrada = TimeEntry::where('user_id', auth()->id())
            ->where('type', 'entrada')
            ->whereDate('datetime', $today)
            ->orderBy('datetime', 'desc')
            ->first();
            
        // Si no hay entrada, no hay jornada abierta
        if (!$ultimaEntrada) {
            return false;
        }
        
        // Obtener la última salida del día
        $ultimaSalida = TimeEntry::where('user_id', auth()->id())
            ->where('type', 'salida')
            ->whereDate('datetime', $today)
            ->orderBy('datetime', 'desc')
            ->first();
        
        // Si no hay salida o la entrada es más reciente que la salida, hay jornada abierta
        return !$ultimaSalida || $ultimaEntrada->datetime > $ultimaSalida->datetime;
    }
    
    protected function hasOpenPause(): bool
    {
        $today = today();
        
        // Obtener la última pausa de inicio del día
        $ultimaPausaInicio = TimeEntry::where('user_id', auth()->id())
            ->where('type', 'pausa_inicio')
            ->whereDate('datetime', $today)
            ->orderBy('datetime', 'desc')
            ->first();
            
        // Si no hay pausa de inicio, no hay pausa abierta
        if (!$ultimaPausaInicio) {
            return false;
        }
        
        // Obtener la última pausa de fin del día
        $ultimaPausaFin = TimeEntry::where('user_id', auth()->id())
            ->where('type', 'pausa_fin')
            ->whereDate('datetime', $today)
            ->orderBy('datetime', 'desc')
            ->first();
        
        // Si no hay pausa fin o el inicio es más reciente que el fin, hay pausa abierta
        return !$ultimaPausaFin || $ultimaPausaInicio->datetime > $ultimaPausaFin->datetime;
    }
}
