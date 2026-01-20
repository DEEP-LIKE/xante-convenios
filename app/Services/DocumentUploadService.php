<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\ClientDocument;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio para gestionar subida y eliminación de documentos del cliente
 *
 * Responsabilidades:
 * - Guardar documentos subidos
 * - Eliminar documentos
 * - Cargar documentos existentes
 * - Gestionar mapeos de tipos de documento
 */
class DocumentUploadService
{
    /**
     * Mapeo de nombres de campo a tipos de documento
     */
    public function getDocumentTypeMap(): array
    {
        return [
            'holder_ine' => 'titular_ine',
            'holder_curp' => 'titular_curp',
            'holder_fiscal_status' => 'titular_constancia_fiscal',
            'holder_proof_address_home' => 'titular_comprobante_domicilio_vivienda',
            'holder_proof_address_titular' => 'titular_comprobante_domicilio_titular',
            'holder_birth_certificate' => 'titular_acta_nacimiento',
            'holder_marriage_certificate' => 'titular_acta_matrimonio',
            'holder_bank_statement' => 'titular_estado_cuenta_bancario',
            'property_notarial_instrument' => 'propiedad_instrumento_notarial',
            'property_tax_receipt' => 'propiedad_recibo_predial',
            'property_water_receipt' => 'propiedad_recibo_agua',
            'property_cfe_receipt' => 'propiedad_recibo_cfe',
        ];
    }

    /**
     * Mapeo inverso: tipos de documento a nombres de campo
     */
    public function getFieldNameMap(): array
    {
        return array_flip($this->getDocumentTypeMap());
    }

    /**
     * Nombres de visualización para cada campo
     */
    public function getDisplayNames(): array
    {
        return [
            'holder_ine' => 'INE',
            'holder_curp' => 'CURP',
            'holder_fiscal_status' => 'Constancia de Situación Fiscal',
            'holder_proof_address_home' => 'Comprobante de Domicilio Vivienda',
            'holder_proof_address_titular' => 'Comprobante de Domicilio Titular',
            'holder_birth_certificate' => 'Acta Nacimiento',
            'holder_marriage_certificate' => 'Acta Matrimonio',
            'holder_bank_statement' => 'Carátula Estado de Cuenta Bancario',
            'property_notarial_instrument' => 'Instrumento Notarial',
            'property_tax_receipt' => 'Recibo Predial',
            'property_water_receipt' => 'Recibo de Agua',
            'property_cfe_receipt' => 'Recibo CFE',
        ];
    }

    /**
     * Obtiene el nombre de visualización de un documento
     */
    public function getDocumentDisplayName(string $fieldName): string
    {
        $displayNames = $this->getDisplayNames();

        return $displayNames[$fieldName] ?? $fieldName;
    }

    /**
     * Guarda un documento del cliente
     */
    public function saveDocument(Agreement $agreement, string $fieldName, $filePath, string $documentName, string $category): void
    {
        try {
            $documentType = $this->getDocumentTypeMap()[$fieldName] ?? $fieldName;

            $fileSize = null;
            $fileName = null;
            $finalFilePath = null;

            // Procesar diferentes tipos de entrada de archivo
            if (is_string($filePath) && ! empty($filePath)) {
                $finalFilePath = $filePath;
                $fileName = basename($filePath);
                if (Storage::disk('private')->exists($filePath)) {
                    $fileSize = Storage::disk('private')->size($filePath);
                }
            } elseif (is_array($filePath) && ! empty($filePath)) {
                $firstFile = $filePath[0];
                if (! empty($firstFile)) {
                    $finalFilePath = $firstFile;
                    $fileName = basename($firstFile);
                    if (Storage::disk('private')->exists($firstFile)) {
                        $fileSize = Storage::disk('private')->size($firstFile);
                    }
                }
            } elseif (is_object($filePath)) {
                if (method_exists($filePath, 'getClientOriginalName')) {
                    $fileName = $filePath->getClientOriginalName();
                    $finalFilePath = $filePath->store('convenios/'.$agreement->id.'/client_documents/'.$category, 'private');
                    $fileSize = $filePath->getSize();
                }
            }

            if (empty($finalFilePath)) {
                throw new \Exception('No se pudo obtener la ruta del archivo');
            }

            if (empty($fileName)) {
                $extension = pathinfo($finalFilePath, PATHINFO_EXTENSION) ?: 'pdf';
                $fileName = $documentName.'_'.time().'.'.$extension;
            }

            // Buscar si ya existe un documento de este tipo
            $existingDocument = ClientDocument::where('agreement_id', $agreement->id)
                ->where('document_type', $documentType)
                ->first();

            if ($existingDocument) {
                // Eliminar el archivo anterior
                if (! empty($existingDocument->file_path) && Storage::disk('private')->exists($existingDocument->file_path)) {
                    Storage::disk('private')->delete($existingDocument->file_path);
                }

                // Actualizar el registro existente
                $existingDocument->update([
                    'document_name' => $documentName,
                    'file_path' => $finalFilePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'uploaded_at' => now(),
                ]);
            } else {
                // Crear nuevo registro
                ClientDocument::create([
                    'agreement_id' => $agreement->id,
                    'document_type' => $documentType,
                    'document_name' => $documentName,
                    'file_path' => $finalFilePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'category' => $category,
                    'uploaded_at' => now(),
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error saving document', [
                'agreement_id' => $agreement->id,
                'field_name' => $fieldName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Elimina un documento del cliente
     */
    public function deleteDocument(Agreement $agreement, string $fieldName): void
    {
        try {
            $documentType = $this->getDocumentTypeMap()[$fieldName] ?? $fieldName;

            $clientDocument = ClientDocument::where('agreement_id', $agreement->id)
                ->where('document_type', $documentType)
                ->first();

            if ($clientDocument) {
                $documentName = $clientDocument->document_name;

                // Eliminar el archivo físico
                if (! empty($clientDocument->file_path) && Storage::disk('private')->exists($clientDocument->file_path)) {
                    Storage::disk('private')->delete($clientDocument->file_path);
                }

                // Eliminar el registro
                $clientDocument->delete();

                Notification::make()
                    ->title('🗑️ Documento Eliminado')
                    ->body('El documento "'.$documentName.'" ha sido eliminado correctamente')
                    ->success()
                    ->duration(3000)
                    ->send();
            }

        } catch (\Exception $e) {
            \Log::error('Error deleting document', [
                'agreement_id' => $agreement->id,
                'field_name' => $fieldName,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('❌ Error al Eliminar')
                ->body('Error al eliminar el documento: '.$e->getMessage())
                ->danger()
                ->duration(4000)
                ->send();
        }
    }

    /**
     * Carga los documentos existentes del cliente
     */
    public function loadDocuments(Agreement $agreement): array
    {
        $documents = [];
        $fieldMap = $this->getFieldNameMap();

        $clientDocuments = ClientDocument::where('agreement_id', $agreement->id)->get();
        $documentsToDelete = [];

        foreach ($clientDocuments as $document) {
            $fieldName = $fieldMap[$document->document_type] ?? null;
            if ($fieldName && ! empty($document->file_path)) {
                // Verificar que el archivo existe físicamente
                if (Storage::disk('private')->exists($document->file_path)) {
                    $documents[$fieldName] = [$document->file_path];
                } else {
                    // Marcar para eliminar de BD
                    $documentsToDelete[] = $document->id;
                }
            }
        }

        // Limpiar registros huérfanos
        if (! empty($documentsToDelete)) {
            ClientDocument::whereIn('id', $documentsToDelete)->delete();
        }

        return $documents;
    }
}
