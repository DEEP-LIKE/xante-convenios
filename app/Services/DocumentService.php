<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\DocumentManager;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ZipArchive;

class DocumentService
{
    /**
     * Sube un documento al sistema
     */
    public function uploadDocument(UploadedFile $file, int $agreementId, string $documentType): DocumentManager
    {
        // Validar el archivo
        $this->validateFile($file);
        
        // Determinar la categoría del documento
        $category = $this->determineDocumentCategory($documentType);
        
        // Generar nombre único para el archivo
        $fileName = $this->generateUniqueFileName($file, $agreementId, $documentType);
        
        // Crear registro en la base de datos
        $document = DocumentManager::create([
            'agreement_id' => $agreementId,
            'document_type' => $documentType,
            'document_category' => $category,
            'original_name' => $file->getClientOriginalName(),
            'file_name' => $fileName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'upload_status' => 'uploading',
            'validation_status' => 'pending',
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
        ]);

        try {
            // Subir el archivo
            $path = $file->storeAs(
                "agreements/{$agreementId}/documents",
                $fileName,
                'public'
            );

            // Actualizar el registro con la ruta del archivo
            $document->update([
                'file_path' => $path,
                'upload_status' => 'uploaded',
            ]);

            // Intentar extraer datos automáticamente (OCR)
            $this->extractDataFromDocument($document);

        } catch (\Exception $e) {
            $document->update(['upload_status' => 'failed']);
            throw $e;
        }

        return $document;
    }

    /**
     * Valida un documento subido
     */
    public function validateDocument(int $documentId, User $validator): ValidationResult
    {
        $document = DocumentManager::findOrFail($documentId);
        
        // Realizar validaciones automáticas
        $validationResults = $this->performAutomaticValidation($document);
        
        // Si pasa las validaciones automáticas, marcar como válido
        if ($validationResults['is_valid']) {
            $document->markAsValid($validator, $validationResults['notes']);
        } else {
            $document->markAsInvalid($validator, $validationResults['notes']);
        }

        return new ValidationResult($validationResults['is_valid'], $validationResults['notes']);
    }

    /**
     * Genera un paquete de documentos para un convenio
     */
    public function generateDocumentPackage(int $agreementId): string
    {
        $agreement = Agreement::with('documents')->findOrFail($agreementId);
        
        // Crear directorio temporal
        $tempDir = storage_path('app/temp/document_packages');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $zipFileName = "convenio_{$agreementId}_documentos_" . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $tempDir . '/' . $zipFileName;
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('No se pudo crear el archivo ZIP');
        }

        // Agregar documentos al ZIP
        foreach ($agreement->documents as $document) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                $filePath = Storage::disk('public')->path($document->file_path);
                $archiveName = $this->sanitizeFileName($document->getDocumentTypeLabel() . '_' . $document->file_name);
                $zip->addFile($filePath, $archiveName);
            }
        }

        // Agregar resumen del convenio
        $summaryContent = $this->generateAgreementSummary($agreement);
        $zip->addFromString('resumen_convenio.txt', $summaryContent);

        $zip->close();

        return $zipPath;
    }

    /**
     * Extrae datos de un documento usando OCR
     */
    public function extractDataFromDocument(DocumentManager $document): array
    {
        $extractedData = [];
        
        try {
            // Simular extracción OCR (aquí se integraría con un servicio real de OCR)
            $extractedData = $this->performOCRExtraction($document);
            
            // Guardar datos extraídos
            $document->updateExtractedData($extractedData);
            
        } catch (\Exception $e) {
            \Log::error("Error extracting data from document {$document->id}: " . $e->getMessage());
        }

        return $extractedData;
    }

    /**
     * Obtiene el checklist de documentos para un convenio
     */
    public function getDocumentChecklist(int $agreementId): array
    {
        $agreement = Agreement::with('documents')->findOrFail($agreementId);
        
        $checklist = [];
        
        // Documentos del titular
        $checklist['titular'] = [
            'titular_ine' => $this->getDocumentStatus($agreement, 'titular_ine'),
            'titular_curp' => $this->getDocumentStatus($agreement, 'titular_curp'),
            'titular_rfc' => $this->getDocumentStatus($agreement, 'titular_rfc'),
            'titular_comprobante_domicilio' => $this->getDocumentStatus($agreement, 'titular_comprobante_domicilio'),
            'titular_acta_nacimiento' => $this->getDocumentStatus($agreement, 'titular_acta_nacimiento'),
            'titular_estado_cuenta' => $this->getDocumentStatus($agreement, 'titular_estado_cuenta'),
        ];

        // Documentos del cónyuge (si aplica)
        if ($this->requiresSpouseDocuments($agreement)) {
            $checklist['conyuge'] = [
                'conyuge_ine' => $this->getDocumentStatus($agreement, 'conyuge_ine'),
                'conyuge_curp' => $this->getDocumentStatus($agreement, 'conyuge_curp'),
                'conyuge_rfc' => $this->getDocumentStatus($agreement, 'conyuge_rfc'),
            ];
        }

        // Documentos de la propiedad
        $checklist['propiedad'] = [
            'propiedad_instrumento_notarial' => $this->getDocumentStatus($agreement, 'propiedad_instrumento_notarial'),
            'propiedad_traslado_dominio' => $this->getDocumentStatus($agreement, 'propiedad_traslado_dominio'),
            'propiedad_recibo_predial' => $this->getDocumentStatus($agreement, 'propiedad_recibo_predial'),
            'propiedad_recibo_agua' => $this->getDocumentStatus($agreement, 'propiedad_recibo_agua'),
            'propiedad_recibo_cfe' => $this->getDocumentStatus($agreement, 'propiedad_recibo_cfe'),
        ];

        return $checklist;
    }

    /**
     * Calcula el porcentaje de completitud de documentos
     */
    public function calculateDocumentCompleteness(int $agreementId): array
    {
        $checklist = $this->getDocumentChecklist($agreementId);
        
        $totalRequired = 0;
        $totalUploaded = 0;
        $totalValid = 0;
        
        foreach ($checklist as $category => $documents) {
            foreach ($documents as $documentType => $status) {
                $totalRequired++;
                
                if ($status['uploaded']) {
                    $totalUploaded++;
                }
                
                if ($status['valid']) {
                    $totalValid++;
                }
            }
        }

        return [
            'total_required' => $totalRequired,
            'total_uploaded' => $totalUploaded,
            'total_valid' => $totalValid,
            'upload_percentage' => $totalRequired > 0 ? round(($totalUploaded / $totalRequired) * 100) : 0,
            'validation_percentage' => $totalRequired > 0 ? round(($totalValid / $totalRequired) * 100) : 0,
        ];
    }

    /**
     * Elimina un documento
     */
    public function deleteDocument(int $documentId): bool
    {
        $document = DocumentManager::findOrFail($documentId);
        
        // Eliminar archivo físico
        $document->deleteFile();
        
        // Eliminar registro de la base de datos
        return $document->delete();
    }

    // Métodos privados

    private function validateFile(UploadedFile $file): void
    {
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception('Tipo de archivo no permitido');
        }

        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
            throw new \Exception('El archivo es demasiado grande (máximo 10MB)');
        }
    }

    private function determineDocumentCategory(string $documentType): string
    {
        if (strpos($documentType, 'titular_') === 0) {
            return 'titular';
        } elseif (strpos($documentType, 'conyuge_') === 0) {
            return 'conyuge';
        } elseif (strpos($documentType, 'propiedad_') === 0) {
            return 'propiedad';
        } else {
            return 'otros';
        }
    }

    private function generateUniqueFileName(UploadedFile $file, int $agreementId, string $documentType): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "{$documentType}_{$agreementId}_{$timestamp}_{$random}.{$extension}";
    }

    private function performAutomaticValidation(DocumentManager $document): array
    {
        $notes = [];
        $isValid = true;

        // Validar que el archivo existe
        if (!Storage::disk('public')->exists($document->file_path)) {
            $notes[] = 'El archivo no existe en el sistema';
            $isValid = false;
        }

        // Validar tamaño del archivo
        if ($document->file_size > 10 * 1024 * 1024) {
            $notes[] = 'El archivo es demasiado grande';
            $isValid = false;
        }

        // Validaciones específicas por tipo de documento
        switch ($document->document_type) {
            case 'titular_ine':
            case 'conyuge_ine':
                if (!in_array($document->mime_type, ['image/jpeg', 'image/png', 'application/pdf'])) {
                    $notes[] = 'La INE debe ser una imagen o PDF';
                    $isValid = false;
                }
                break;
                
            case 'titular_curp':
            case 'conyuge_curp':
                // Validar que sea un documento reciente (mes corriente)
                $notes[] = 'Verificar que sea del mes corriente';
                break;
        }

        return [
            'is_valid' => $isValid,
            'notes' => $notes
        ];
    }

    private function performOCRExtraction(DocumentManager $document): array
    {
        // Aquí se integraría con un servicio real de OCR como Tesseract, AWS Textract, etc.
        // Por ahora, retornamos datos simulados
        
        $extractedData = [];
        
        switch ($document->document_type) {
            case 'titular_ine':
            case 'conyuge_ine':
                $extractedData = [
                    'nombre' => 'Extraído por OCR',
                    'fecha_nacimiento' => null,
                    'curp' => null,
                    'confidence' => 0.85
                ];
                break;
                
            case 'titular_curp':
            case 'conyuge_curp':
                $extractedData = [
                    'curp' => 'Extraído por OCR',
                    'nombre' => 'Extraído por OCR',
                    'confidence' => 0.90
                ];
                break;
        }

        return $extractedData;
    }

    private function getDocumentStatus(Agreement $agreement, string $documentType): array
    {
        $document = $agreement->documents->where('document_type', $documentType)->first();
        
        return [
            'uploaded' => $document !== null,
            'valid' => $document && $document->validation_status === 'valid',
            'pending_validation' => $document && $document->validation_status === 'pending',
            'invalid' => $document && $document->validation_status === 'invalid',
            'document' => $document,
        ];
    }

    private function requiresSpouseDocuments(Agreement $agreement): bool
    {
        return in_array($agreement->holder_civil_status, ['casado', 'union_libre']);
    }

    private function generateAgreementSummary(Agreement $agreement): string
    {
        $summary = "RESUMEN DEL CONVENIO\n";
        $summary .= "=====================\n\n";
        $summary .= "ID del Convenio: {$agreement->id}\n";
        $clientName = isset($agreement->client) ? $agreement->client->name : 'N/A';
        $summary .= "Cliente: {$clientName}\n";
        $summary .= "Estado: {$agreement->getStatusLabelAttribute()}\n";
        $summary .= "Fecha de Creación: {$agreement->created_at->format('d/m/Y H:i')}\n";
        $summary .= "Progreso: {$agreement->completion_percentage}%\n\n";
        
        if ($agreement->valor_convenio) {
            $summary .= "DATOS FINANCIEROS\n";
            $summary .= "=================\n";
            $summary .= "Valor del Convenio: $" . number_format($agreement->valor_convenio, 2) . "\n";
            $summary .= "Precio Promoción: $" . number_format($agreement->precio_promocion, 2) . "\n";
            $summary .= "Ganancia Final: $" . number_format($agreement->ganancia_final, 2) . "\n\n";
        }
        
        return $summary;
    }

    private function sanitizeFileName(string $fileName): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
    }
}
