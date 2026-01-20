<?php

namespace App\Actions\Agreements;

use App\Models\Agreement;
use App\Models\ClientDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerateDocumentsZipAction
{
    /**
     * Genera un archivo ZIP con todos los documentos del convenio.
     *
     * @return string Ruta absoluta al archivo ZIP generado
     *
     * @throws \Exception
     */
    public function execute(Agreement $agreement): string
    {
        $generatedDocuments = $agreement->generatedDocuments;
        $clientDocuments = ClientDocument::where('agreement_id', $agreement->id)->get();

        Log::info('GenerateDocumentsZipAction - Documents found', [
            'agreement_id' => $agreement->id,
            'generated_count' => $generatedDocuments->count(),
            'client_count' => $clientDocuments->count(),
        ]);

        if ($generatedDocuments->isEmpty() && $clientDocuments->isEmpty()) {
            throw new \Exception('No hay documentos disponibles para descargar');
        }

        // Crear un ZIP con todos los documentos
        $timestamp = time();
        $zipFileName = 'convenio_'.$agreement->id.'_'.$timestamp.'.zip';
        $zipPath = storage_path('app/temp/'.$zipFileName);

        // Crear directorio temporal si no existe
        if (! file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;

        $zipResult = $zip->open($zipPath, ZipArchive::CREATE);
        if ($zipResult !== true) {
            throw new \Exception('No se pudo crear el archivo ZIP');
        }

        $addedFiles = 0;

        // Agregar documentos generados (PDFs del sistema)
        foreach ($generatedDocuments as $document) {
            $filePath = Storage::disk('private')->path($document->file_path);

            if (file_exists($filePath)) {
                $documentName = $document->document_name ?? 'documento_generado_'.$document->id;
                $cleanDocumentName = $this->cleanFileName($documentName);
                $zipEntryName = 'generados/'.$cleanDocumentName.'.pdf';

                try {
                    $zip->addFile($filePath, $zipEntryName);
                    $addedFiles++;
                } catch (\Exception $e) {
                    Log::error('Error adding generated file to ZIP', [
                        'error' => $e->getMessage(),
                        'file' => $filePath,
                    ]);
                }
            } else {
                Log::warning('Generated document file not found', ['path' => $filePath]);
            }
        }

        // Agregar documentos del cliente (subidos en paso 2)
        foreach ($clientDocuments as $document) {
            $filePath = Storage::disk('private')->path($document->file_path);

            if (file_exists($filePath)) {
                $documentName = $document->document_name ?? 'documento_cliente_'.$document->id;
                $cleanDocumentName = $this->cleanFileName($documentName);
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $zipEntryName = 'cliente/'.$cleanDocumentName.'.'.$extension;

                try {
                    $zip->addFile($filePath, $zipEntryName);
                    $addedFiles++;
                } catch (\Exception $e) {
                    Log::error('Error adding client file to ZIP', [
                        'error' => $e->getMessage(),
                        'file' => $filePath,
                    ]);
                }
            } else {
                Log::warning('Client document file not found', ['path' => $filePath]);
            }
        }

        $zip->close();

        if ($addedFiles === 0) {
            throw new \Exception('No se encontraron archivos físicos para agregar al ZIP');
        }

        return $zipPath;
    }

    /**
     * Limpiar nombre de archivo para evitar caracteres problemáticos en ZIP
     */
    private function cleanFileName(?string $fileName): string
    {
        // Manejar valores nulos o vacíos
        if (empty($fileName)) {
            return 'documento_'.time();
        }

        // Enfoque ultra-simple: solo letras, números y guiones bajos
        // Convertir todo a minúsculas y remover acentos
        $cleanName = strtolower($fileName);
        $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $cleanName);

        // Reemplazar CUALQUIER cosa que no sea letra o número con guión bajo
        $cleanName = preg_replace('/[^a-z0-9]/', '_', $cleanName);

        // Remover guiones bajos múltiples
        $cleanName = preg_replace('/_+/', '_', $cleanName);

        // Remover guiones bajos al inicio y final
        $cleanName = trim($cleanName, '_');

        // Limitar longitud
        if (strlen($cleanName) > 50) {
            $cleanName = substr($cleanName, 0, 50);
        }

        // Asegurar que no esté vacío
        if (empty($cleanName)) {
            $cleanName = 'doc_'.time();
        }

        return $cleanName;
    }
}
