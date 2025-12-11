<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Establecer el usuario que sube el documento
        $data['uploaded_by'] = auth()->id();
        
        // Si file_path contiene el archivo, extraer información
        if (isset($data['file_path']) && $data['file_path']) {
            $filePath = $data['file_path'];
            
            // Obtener el nombre real del archivo desde la ruta
            $data['file_name'] = basename($filePath);
            
            // Obtener el tamaño del archivo
            try {
                $fullPath = Storage::disk('public')->path($filePath);
                if (file_exists($fullPath)) {
                    $data['file_size'] = filesize($fullPath);
                } else {
                    $data['file_size'] = 0;
                }
            } catch (\Exception $e) {
                $data['file_size'] = 0;
            }
        } else {
            // Valores por defecto si no hay archivo
            $data['file_name'] = 'documento';
            $data['file_size'] = 0;
        }

        return $data;
    }
}
