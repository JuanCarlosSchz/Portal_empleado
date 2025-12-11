<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Exportar Todo a Excel')
                ->color('success')
                ->exports([
                    ExcelExport::make()
                        ->withFilename('trabajadores-completo-' . date('Y-m-d'))
                        ->withColumns([
                            Column::make('name')->heading('Nombre'),
                            Column::make('email')->heading('Email'),
                            Column::make('dni')->heading('DNI'),
                            Column::make('monthly_hours')
                                ->heading('Horas del Mes')
                                ->formatStateUsing(function ($record) {
                                    $startOfMonth = now()->startOfMonth();
                                    $endOfMonth = now()->endOfMonth();
                                    
                                    $entries = $record->timeEntries()
                                        ->whereBetween('datetime', [$startOfMonth, $endOfMonth])
                                        ->orderBy('datetime')
                                        ->get();
                                    
                                    $totalMinutes = 0;
                                    $lastEntry = null;
                                    $pauseStart = null;
                                    
                                    foreach ($entries as $entry) {
                                        if ($entry->type === 'entrada') {
                                            $lastEntry = $entry->datetime;
                                        } elseif ($entry->type === 'salida' && $lastEntry) {
                                            $minutes = $lastEntry->diffInMinutes($entry->datetime);
                                            $totalMinutes += $minutes;
                                            $lastEntry = null;
                                        } elseif ($entry->type === 'pausa_inicio') {
                                            $pauseStart = $entry->datetime;
                                        } elseif ($entry->type === 'pausa_fin' && $pauseStart) {
                                            $pauseMinutes = $pauseStart->diffInMinutes($entry->datetime);
                                            $totalMinutes -= $pauseMinutes;
                                            $pauseStart = null;
                                        }
                                    }
                                    
                                    $hours = floor($totalMinutes / 60);
                                    $minutes = $totalMinutes % 60;
                                    
                                    return sprintf('%02d:%02d', $hours, $minutes);
                                }),
                            Column::make('fichajes_count')
                                ->heading('Fichajes del Mes')
                                ->formatStateUsing(function ($record) {
                                    return $record->timeEntries()
                                        ->whereBetween('datetime', [now()->startOfMonth(), now()->endOfMonth()])
                                        ->count();
                                }),
                        ]),
                ]),
        ];
    }
}
