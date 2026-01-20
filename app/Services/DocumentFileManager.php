<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\ClientDocument;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio para gestionar archivos físicos de documentos
 *
 * Responsabilidades:
 * - Limpiar archivos huérfanos
 * - Verificar existencia de archivos
 * - Eliminar archivos físicos
 * - Obtener información de archivos
 */
class DocumentFileManager
{
    /**
     * Limpia archivos huérfanos del disco que no tienen registro en BD
     */
    public function cleanOrphanFiles(Agreement $agreement): void
    {
        try {
            $clientDocumentPaths = [
                'client_documents/'.$agreement->id.'/titular',
                'client_documents/'.$agreement->id.'/propiedad',
            ];

            // Obtener todos los file_path de la BD para este convenio
            $dbFilePaths = ClientDocument::where('agreement_id', $agreement->id)
                ->pluck('file_path')
                ->toArray();

            foreach ($clientDocumentPaths as $path) {
                if (Storage::disk('private')->exists($path)) {
                    $files = Storage::disk('private')->files($path);

                    foreach ($files as $file) {
                        // Si el archivo no está en la BD, eliminarlo
                        if (! in_array($file, $dbFilePaths)) {
                            Storage::disk('private')->delete($file);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silencioso - no queremos interrumpir el flujo por limpieza
            \Log::warning('Error cleaning orphan files', [
                'agreement_id' => $agreement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verifica si un archivo existe en el disco
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk('private')->exists($path);
    }

    /**
     * Elimina un archivo del disco
     */
    public function deleteFile(string $path): bool
    {
        try {
            if ($this->fileExists($path)) {
                return Storage::disk('private')->delete($path);
            }

            return false;
        } catch (\Exception $e) {
            \Log::error('Error deleting file', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Obtiene el tamaño de un archivo
     */
    public function getFileSize(string $path): ?int
    {
        try {
            if ($this->fileExists($path)) {
                return Storage::disk('private')->size($path);
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error getting file size', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtiene la URL de descarga de un archivo
     */
    public function getDownloadUrl(string $path): ?string
    {
        try {
            if ($this->fileExists($path)) {
                return Storage::disk('private')->url($path);
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error getting download URL', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
