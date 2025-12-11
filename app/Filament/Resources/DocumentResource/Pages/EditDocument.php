<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si se cambió el archivo, actualizar información
        if (isset($data['file_path']) && $data['file_path'] !== $this->record->file_path) {
            $filePath = $data['file_path'];
            
            // Obtener el nombre real del archivo
            $data['file_name'] = basename($filePath);
            
            // Obtener el tamaño del archivo
            $fullPath = Storage::disk('public')->path($filePath);
            if (file_exists($fullPath)) {
                $data['file_size'] = filesize($fullPath);
            }
        }

        return $data;
    }
}
