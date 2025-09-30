<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\GeneratedDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PdfGenerationService
{
    /**
     * Genera todos los documentos PDF para un convenio
     */
    public function generateAllDocuments(Agreement $agreement): array
    {
        $documents = [];
        
        $templates = [
            'acuerdo_promocion' => 'Acuerdo de Promoción Inmobiliaria',
            'datos_generales' => 'Datos Generales - Fase I',
            'checklist_expediente' => 'Checklist de Expediente Básico',
            'condiciones_comercializacion' => 'Condiciones para Comercialización',
        ];

        foreach ($templates as $type => $name) {
            try {
                $document = $this->generateSingleDocument($agreement, $type, $name);
                $documents[] = $document;
                
                Log::info("Documento generado exitosamente", [
                    'agreement_id' => $agreement->id,
                    'document_type' => $type,
                    'file_path' => $document->file_path
                ]);
                
            } catch (\Exception $e) {
                Log::error("Error generando documento {$type} para Agreement #{$agreement->id}: " . $e->getMessage());
                throw $e;
            }
        }

        // Actualizar estado del convenio
        $agreement->update([
            'status' => 'documents_generated',
            'documents_generated_at' => now(),
            'can_return_to_wizard1' => false, // CRÍTICO: No se puede regresar
            'current_wizard' => 2,
            'wizard2_current_step' => 1,
        ]);

        Log::info("Todos los documentos generados para Agreement #{$agreement->id}", [
            'documents_count' => count($documents)
        ]);

        return $documents;
    }

    /**
     * Genera un documento PDF individual
     */
    private function generateSingleDocument(Agreement $agreement, string $type, string $name): GeneratedDocument
    {
        // Preparar datos para la plantilla
        $data = $this->prepareTemplateData($agreement);
        
        // Renderizar HTML desde Blade
        $html = view("pdfs.templates.{$type}", $data)->render();
        
        // Configurar PDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('letter')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);
        
        // Generar nombre y ruta del archivo
        $fileName = $this->generateFileName($agreement, $type);
        $directory = "convenios/{$agreement->id}/generated";
        $filePath = "{$directory}/{$fileName}";
        
        // Asegurar que el directorio existe
        Storage::disk('private')->makeDirectory($directory);
        
        // Generar y guardar PDF
        $pdfOutput = $pdf->output();
        Storage::disk('private')->put($filePath, $pdfOutput);
        
        // Registrar en base de datos
        return GeneratedDocument::create([
            'agreement_id' => $agreement->id,
            'document_type' => $type,
            'document_name' => $name,
            'file_path' => $filePath,
            'template_used' => "pdfs.templates.{$type}",
            'file_size' => strlen($pdfOutput),
            'generated_at' => now(),
        ]);
    }

    /**
     * Prepara los datos para las plantillas
     */
    private function prepareTemplateData(Agreement $agreement): array
    {
        $wizardData = $agreement->wizard_data ?? [];
        
        // Calcular porcentaje de comisión desde los datos financieros
        $valorConvenio = floatval(str_replace(',', '', $wizardData['valor_convenio'] ?? 0));
        $montoComisionSinIva = floatval(str_replace(',', '', $wizardData['monto_comision_sin_iva'] ?? 0));
        $porcentajeComision = $valorConvenio > 0 ? ($montoComisionSinIva / $valorConvenio) * 100 : 6.5;
        
        // Nombres de meses en español
        $monthNames = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
        
        return [
            'agreement' => $agreement,
            'client' => $agreement->client,
            'wizardData' => $wizardData, // Datos completos para la plantilla
            
            // Datos del cliente titular
            'holder_name' => $wizardData['holder_name'] ?? '',
            'holder_email' => $wizardData['holder_email'] ?? '',
            'holder_phone' => $wizardData['holder_phone'] ?? '',
            'holder_curp' => $wizardData['holder_curp'] ?? '',
            'holder_rfc' => $wizardData['holder_rfc'] ?? '',
            'holder_birthdate' => $wizardData['holder_birthdate'] ?? '',
            'holder_civil_status' => $wizardData['holder_civil_status'] ?? '',
            'holder_occupation' => $wizardData['holder_occupation'] ?? '',
            'holder_current_address' => $wizardData['holder_current_address'] ?? '',
            'holder_municipality' => $wizardData['holder_municipality'] ?? '',
            'holder_state' => $wizardData['holder_state'] ?? '',
            
            // Datos del cónyuge
            'spouse_name' => $wizardData['spouse_name'] ?? '',
            'spouse_email' => $wizardData['spouse_email'] ?? '',
            'spouse_phone' => $wizardData['spouse_phone'] ?? '',
            'spouse_curp' => $wizardData['spouse_curp'] ?? '',
            'spouse_rfc' => $wizardData['spouse_rfc'] ?? '',
            
            // Datos de la propiedad
            'domicilio_convenio' => $wizardData['domicilio_convenio'] ?? '',
            'comunidad' => $wizardData['comunidad'] ?? '',
            'tipo_vivienda' => $wizardData['tipo_vivienda'] ?? '',
            'prototipo' => $wizardData['prototipo'] ?? '',
            'lote' => $wizardData['lote'] ?? '',
            'manzana' => $wizardData['manzana'] ?? '',
            'etapa' => $wizardData['etapa'] ?? '',
            'municipio_propiedad' => $wizardData['municipio_propiedad'] ?? '',
            'estado_propiedad' => $wizardData['estado_propiedad'] ?? '',
            'numero_interior' => $wizardData['numero_interior'] ?? '',
            
            // Estados y ubicaciones
            'property_state' => $wizardData['estado_propiedad'] ?? $wizardData['estado'] ?? '',
            'property_municipality' => $wizardData['municipio_propiedad'] ?? $wizardData['municipio'] ?? '',
            
            // Datos financieros
            'valor_convenio' => $valorConvenio,
            'precio_promocion' => floatval(str_replace(',', '', $wizardData['precio_promocion'] ?? 0)),
            'valor_compraventa' => floatval(str_replace(',', '', $wizardData['valor_compraventa'] ?? 0)),
            'monto_comision_sin_iva' => $montoComisionSinIva,
            'comision_total_pagar' => floatval(str_replace(',', '', $wizardData['comision_total_pagar'] ?? 0)),
            'ganancia_final' => floatval(str_replace(',', '', $wizardData['ganancia_final'] ?? 0)),
            'isr' => floatval(str_replace(',', '', $wizardData['isr'] ?? 0)),
            'cancelacion_hipoteca' => floatval(str_replace(',', '', $wizardData['cancelacion_hipoteca'] ?? 0)),
            'monto_credito' => floatval(str_replace(',', '', $wizardData['monto_credito'] ?? 0)),
            'tipo_credito' => $wizardData['tipo_credito'] ?? '',
            
            // Porcentajes y textos de comisión
            'porcentaje_comision' => number_format($porcentajeComision, 1),
            'porcentaje_comision_letras' => $this->numberToWords($porcentajeComision),
            'precio_promocion_letras' => $this->numberToWords(floatval(str_replace(',', '', $wizardData['precio_promocion'] ?? 0))),
            
            // Fechas
            'fecha_actual' => now()->format('d/m/Y'),
            'fecha_completa' => now()->format('d \d\e F \d\e Y'),
            'day' => now()->format('d'),
            'month' => $monthNames[now()->format('n')] ?? 'enero',
            'year' => now()->format('Y'),
            'monthNames' => $monthNames,
            'xante_id' => $agreement->client_xante_id ?? '',
            
            // Datos bancarios (valores por defecto de XANTE)
            'bank_name' => $wizardData['bank_name'] ?? 'BBVA',
            'bank_account' => $wizardData['bank_account'] ?? '0123456789',
            'bank_clabe' => $wizardData['bank_clabe'] ?? '012345678901234567',
            
            // Contactos adicionales
            'ac_name' => $wizardData['ac_name'] ?? '',
            'ac_phone' => $wizardData['ac_phone'] ?? '',
            'ac_quota' => $wizardData['ac_quota'] ?? 0,
            'private_president_name' => $wizardData['private_president_name'] ?? '',
            'private_president_phone' => $wizardData['private_president_phone'] ?? '',
            'private_president_quota' => $wizardData['private_president_quota'] ?? 0,
        ];
    }
    
    /**
     * Convierte números a palabras (implementación básica)
     */
    private function numberToWords(float $number): string
    {
        try {
            if (class_exists('\NumberFormatter')) {
                $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
                return $formatter->format($number);
            }
        } catch (\Exception $e) {
            Log::warning("NumberFormatter no disponible: " . $e->getMessage());
        }
        
        // Fallback: implementación básica para números comunes
        $number = round($number, 1);
        
        if ($number == 6.5) return 'seis punto cinco por ciento';
        if ($number == 7.0) return 'siete por ciento';
        if ($number == 7.5) return 'siete punto cinco por ciento';
        if ($number == 8.0) return 'ocho por ciento';
        
        // Para otros números, usar formato simple
        return number_format($number, 1) . ' por ciento';
    }

    /**
     * Genera el nombre del archivo PDF
     */
    private function generateFileName(Agreement $agreement, string $type): string
    {
        $clientId = $agreement->client_xante_id ?? $agreement->id;
        $timestamp = now()->format('YmdHis');
        
        return "{$clientId}_{$type}_{$timestamp}.pdf";
    }

    /**
     * Verifica si todos los documentos fueron generados correctamente
     */
    public function verifyDocumentsGenerated(Agreement $agreement): bool
    {
        $expectedTypes = ['acuerdo_promocion', 'datos_generales', 'checklist_expediente', 'condiciones_comercializacion'];
        $generatedTypes = $agreement->generatedDocuments()->pluck('document_type')->toArray();
        
        foreach ($expectedTypes as $type) {
            if (!in_array($type, $generatedTypes)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Obtiene el tamaño total de todos los documentos generados
     */
    public function getTotalDocumentsSize(Agreement $agreement): int
    {
        return $agreement->generatedDocuments()->sum('file_size');
    }

    /**
     * Elimina todos los documentos generados (solo para casos de error)
     */
    public function cleanupGeneratedDocuments(Agreement $agreement): void
    {
        $documents = $agreement->generatedDocuments;
        
        foreach ($documents as $document) {
            // Eliminar archivo físico
            if (Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }
            
            // Eliminar registro de BD
            $document->delete();
        }
        
        Log::info("Documentos limpiados para Agreement #{$agreement->id}");
    }
}
