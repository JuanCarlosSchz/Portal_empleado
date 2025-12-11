<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use App\Models\User;
use App\Models\TimeEntry;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ManageTimeEntries extends Page
{
    protected static string $resource = TimeEntryResource::class;

    protected static string $view = 'filament.resources.time-entry-resource.pages.manage-time-entries';

    public ?int $selectedUserId = null;
    public ?string $selectedMonth = null;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
    }

    public function getWorkers()
    {
        return User::where('role', 'trabajador')
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $entriesCount = TimeEntry::where('user_id', $user->id)
                    ->whereYear('datetime', substr($this->selectedMonth, 0, 4))
                    ->whereMonth('datetime', substr($this->selectedMonth, 5, 2))
                    ->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'dni' => $user->dni,
                    'entries_count' => $entriesCount,
                ];
            });
    }

    public function getTimeEntries()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        return TimeEntry::where('user_id', $this->selectedUserId)
            ->whereYear('datetime', substr($this->selectedMonth, 0, 4))
            ->whereMonth('datetime', substr($this->selectedMonth, 5, 2))
            ->orderBy('datetime')
            ->get()
            ->groupBy(fn($entry) => $entry->datetime->format('Y-m-d'))
            ->map(function ($dayEntries, $date) {
                $entrada = $dayEntries->firstWhere('type', 'entrada');
                $salida = $dayEntries->firstWhere('type', 'salida');
                $pausas = $dayEntries->whereIn('type', ['pausa_inicio', 'pausa_fin']);

                $totalMinutes = 0;
                $pauseMinutes = 0;

                if ($entrada && $salida) {
                    $totalMinutes = $entrada->datetime->diffInMinutes($salida->datetime);
                }

                $pausaInicio = null;
                foreach ($pausas as $pausa) {
                    if ($pausa->type === 'pausa_inicio') {
                        $pausaInicio = $pausa->datetime;
                    } elseif ($pausa->type === 'pausa_fin' && $pausaInicio) {
                        $pauseMinutes += $pausaInicio->diffInMinutes($pausa->datetime);
                        $pausaInicio = null;
                    }
                }

                $workedMinutes = $totalMinutes - $pauseMinutes;

                return [
                    'date' => $date,
                    'entries' => $dayEntries,
                    'entrada' => $entrada,
                    'salida' => $salida,
                    'pausas' => $pausas,
                    'total_hours' => floor($workedMinutes / 60) . 'h ' . ($workedMinutes % 60) . 'min',
                    'pause_time' => floor($pauseMinutes / 60) . 'h ' . ($pauseMinutes % 60) . 'min',
                ];
            });
    }

    public function selectWorker($userId)
    {
        $this->selectedUserId = $userId;
    }

    public function getMonthlyTotal()
    {
        if (!$this->selectedUserId) {
            return '0h 0min';
        }

        $entries = TimeEntry::where('user_id', $this->selectedUserId)
            ->whereYear('datetime', substr($this->selectedMonth, 0, 4))
            ->whereMonth('datetime', substr($this->selectedMonth, 5, 2))
            ->orderBy('datetime')
            ->get();

        $totalMinutes = 0;

        $grouped = $entries->groupBy(fn($entry) => $entry->datetime->format('Y-m-d'));

        foreach ($grouped as $dayEntries) {
            $entrada = $dayEntries->firstWhere('type', 'entrada');
            $salida = $dayEntries->firstWhere('type', 'salida');
            $pausas = $dayEntries->whereIn('type', ['pausa_inicio', 'pausa_fin']);

            if ($entrada && $salida) {
                $dayMinutes = $entrada->datetime->diffInMinutes($salida->datetime);
                
                $pausaInicio = null;
                foreach ($pausas as $pausa) {
                    if ($pausa->type === 'pausa_inicio') {
                        $pausaInicio = $pausa->datetime;
                    } elseif ($pausa->type === 'pausa_fin' && $pausaInicio) {
                        $dayMinutes -= $pausaInicio->diffInMinutes($pausa->datetime);
                        $pausaInicio = null;
                    }
                }

                $totalMinutes += $dayMinutes;
            }
        }

        return floor($totalMinutes / 60) . 'h ' . ($totalMinutes % 60) . 'min';
    }
}
