<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreateTimeEntry extends CreateRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function handleRecordCreation(array $data): TimeEntry
    {
        // Si es el formulario completo de jornada, crear múltiples registros
        if (isset($data['date']) && isset($data['entrada_time'])) {
            $date = $data['date'];
            $userId = $data['user_id'];
            $location = $data['location'] ?? null;
            $notes = $data['notes'] ?? null;

            // Crear registro de entrada
            $entradaDatetime = Carbon::parse($date . ' ' . $data['entrada_time']);
            $entradaRecord = TimeEntry::create([
                'user_id' => $userId,
                'type' => 'entrada',
                'datetime' => $entradaDatetime,
                'location' => $location,
                'notes' => $notes,
            ]);

            // Crear registros de pausas si existen
            if (isset($data['pausas']) && is_array($data['pausas'])) {
                foreach ($data['pausas'] as $pausa) {
                    if (isset($pausa['inicio']) && isset($pausa['fin'])) {
                        // Pausa inicio
                        $pausaInicioDatetime = Carbon::parse($date . ' ' . $pausa['inicio']);
                        TimeEntry::create([
                            'user_id' => $userId,
                            'type' => 'pausa_inicio',
                            'datetime' => $pausaInicioDatetime,
                            'location' => $location,
                        ]);

                        // Pausa fin
                        $pausaFinDatetime = Carbon::parse($date . ' ' . $pausa['fin']);
                        TimeEntry::create([
                            'user_id' => $userId,
                            'type' => 'pausa_fin',
                            'datetime' => $pausaFinDatetime,
                            'location' => $location,
                        ]);
                    }
                }
            }

            // Crear registro de salida
            if (isset($data['salida_time'])) {
                $salidaDatetime = Carbon::parse($date . ' ' . $data['salida_time']);
                TimeEntry::create([
                    'user_id' => $userId,
                    'type' => 'salida',
                    'datetime' => $salidaDatetime,
                    'location' => $location,
                    'notes' => $notes,
                ]);
            }

            // Retornar el registro de entrada como referencia
            return $entradaRecord;
        }

        // Si es un registro simple individual, crear normalmente
        return TimeEntry::create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jornada registrada correctamente';
    }
}
