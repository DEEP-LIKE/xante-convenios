<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\GeneratedDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfGenerationService
{
    /**
     * Genera todos los documentos PDF para un convenio
     */
    public function generateAllDocuments(Agreement $agreement): array
    {
        // IMPORTANTE: Limpiar documentos existentes antes de generar nuevos
        // Solo elimina las referencias en BD, no los archivos físicos
        if ($agreement->generatedDocuments()->count() > 0) {
            Log::info('Limpiando referencias de documentos existentes antes de regenerar', [
                'agreement_id' => $agreement->id,
                'documentos_existentes' => $agreement->generatedDocuments()->count(),
            ]);

            // Eliminar solo las referencias de la base de datos
            $agreement->generatedDocuments()->delete();
        }

        $documents = [];

        // Plantillas Blade que se generan dinámicamente (4 documentos)
        $templates = [
            'acuerdo_promocion' => 'Acuerdo de Promoción Inmobiliaria',
            'datos_generales' => 'Datos Generales - Fase I',
            'checklist_expediente' => 'Checklist de Expediente Básico',
            'condiciones_comercializacion' => 'Condiciones para Comercialización',
        ];

        // Documentos originales que se copian tal cual (2 documentos)
        $originalDocuments = [
            'aviso_privacidad' => 'Aviso de Privacidad',
            'euc_venta_convenio' => 'EUC Venta Convenio',
        ];

        // Generar documentos desde plantillas Blade
        foreach ($templates as $type => $name) {
            try {
                Log::info('Iniciando generación de plantilla Blade', [
                    'agreement_id' => $agreement->id,
                    'document_type' => $type,
                    'template' => "pdfs.templates.{$type}",
                ]);

                $document = $this->generateSingleDocument($agreement, $type, $name);
                $documents[] = $document;

                Log::info('Documento generado exitosamente', [
                    'agreement_id' => $agreement->id,
                    'document_type' => $type,
                    'file_path' => $document->file_path,
                ]);

            } catch (\Exception $e) {
                Log::error("Error generando documento {$type} para Agreement #{$agreement->id}: ".$e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        // Copiar documentos originales
        foreach ($originalDocuments as $type => $name) {
            try {
                Log::info('Iniciando copia de documento original', [
                    'agreement_id' => $agreement->id,
                    'document_type' => $type,
                    'document_name' => $name,
                ]);

                $document = $this->copyOriginalDocument($agreement, $type, $name);
                $documents[] = $document;

                Log::info('Documento original copiado exitosamente', [
                    'agreement_id' => $agreement->id,
                    'document_type' => $type,
                    'file_path' => $document->file_path,
                ]);

            } catch (\Exception $e) {
                Log::error("Error copiando documento original {$type} para Agreement #{$agreement->id}: ".$e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString(),
                ]);
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
            'documents_count' => count($documents),
        ]);

        return $documents;
    }

    /**
     * Copia un documento PDF original
     */
    private function copyOriginalDocument(Agreement $agreement, string $type, string $name): GeneratedDocument
    {
        // Mapeo de tipos a nombres de archivos originales
        $originalFiles = [
            'aviso_privacidad' => 'AVISO_DE_PRIVACIDAD.pdf',
            'euc_venta_convenio' => 'EUC_VENTA_CONVENIO.pdf',
        ];

        if (! isset($originalFiles[$type])) {
            throw new \Exception("Archivo original no encontrado para tipo: {$type}");
        }

        $originalFileName = $originalFiles[$type];
        $originalPath = resource_path("views/pdfs/orginal_pdf/{$originalFileName}");

        Log::info('Intentando acceder al archivo original', [
            'type' => $type,
            'original_file' => $originalFileName,
            'full_path' => $originalPath,
            'file_exists' => file_exists($originalPath),
        ]);

        if (! file_exists($originalPath)) {
            throw new \Exception("Archivo original no existe: {$originalPath}");
        }

        // Generar nombre y ruta del archivo de destino
        $fileName = $this->generateFileName($agreement, $type);
        $directory = "convenios/{$agreement->id}/generated";
        $filePath = "{$directory}/{$fileName}";

        // S3 no requiere makeDirectory para carpetas (se crean mediante prefijos al subir archivos)
        Log::info('Subiendo documento original a S3', ['path' => $filePath]);

        // Copiar archivo original
        $fileContent = file_get_contents($originalPath);
        Storage::disk('s3')->put($filePath, $fileContent);

        // Registrar en base de datos
        return GeneratedDocument::create([
            'agreement_id' => $agreement->id,
            'document_type' => $type,
            'document_name' => $name,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'template_used' => "original_pdf/{$originalFileName}",
            'file_size' => strlen($fileContent),
            'generated_at' => now(),
        ]);
    }

    /**
     * Genera un documento PDF individual
     */
    private function generateSingleDocument(Agreement $agreement, string $type, string $name): GeneratedDocument
    {
        try {
            // Preparar datos para la plantilla
            $data = $this->prepareTemplateData($agreement);

            // Para el checklist, agregar variables específicas
            if ($type === 'checklist_expediente') {
                $data['uploadedDocuments'] = []; // Lista vacía inicialmente
                $data['isUpdated'] = false; // Paso 1: nada marcado
            }

            Log::info('Datos preparados para plantilla', [
                'agreement_id' => $agreement->id,
                'document_type' => $type,
                'data_keys' => array_keys($data),
            ]);

            // Verificar que la vista existe
            $viewPath = "pdfs.templates.{$type}";
            if (! view()->exists($viewPath)) {
                throw new \Exception("La plantilla Blade no existe: {$viewPath}");
            }

            // Renderizar HTML desde Blade
            $html = view($viewPath, $data)->render();

            Log::info('HTML renderizado exitosamente', [
                'agreement_id' => $agreement->id,
                'document_type' => $type,
                'html_length' => strlen($html),
            ]);

        } catch (\Exception $e) {
            Log::error('Error en preparación de datos o renderizado', [
                'agreement_id' => $agreement->id,
                'document_type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Configurar PDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('letter')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        // Generar nombre y ruta del archivo
        $fileName = $this->generateFileName($agreement, $type);
        $directory = "convenios/{$agreement->id}/generated";
        $filePath = "{$directory}/{$fileName}";

        // S3 no requiere makeDirectory para carpetas
        Log::info('Subiendo PDF generado a S3', ['path' => $filePath]);

        // Generar y guardar PDF
        $pdfOutput = $pdf->output();
        Storage::disk('s3')->put($filePath, $pdfOutput);

        // Registrar en base de datos
        return GeneratedDocument::create([
            'agreement_id' => $agreement->id,
            'document_type' => $type,
            'document_name' => $name,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'template_used' => "pdfs.templates.{$type}",
            'file_size' => strlen($pdfOutput),
            'generated_at' => now(),
        ]);
    }

    /**
     * Prepara los datos para las plantillas
     */
    public function prepareTemplateData(Agreement $agreement): array
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
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
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
            'holder_current_address' => $wizardData['holder_current_address'] ?? $wizardData['current_address'] ?? '',
            'holder_municipality' => $wizardData['holder_municipality'] ?? $wizardData['municipality'] ?? '',
            'holder_state' => $wizardData['holder_state'] ?? $wizardData['state'] ?? '',
            'holder_delivery_file' => $wizardData['holder_delivery_file'] ?? '',
            'holder_regime_type' => $wizardData['holder_regime_type'] ?? '',
            'holder_office_phone' => $wizardData['holder_office_phone'] ?? '',
            'holder_additional_contact_phone' => $wizardData['holder_additional_contact_phone'] ?? '',
            'holder_neighborhood' => $wizardData['holder_neighborhood'] ?? $wizardData['neighborhood'] ?? '',
            'holder_postal_code' => $wizardData['holder_postal_code'] ?? $wizardData['postal_code'] ?? '',

            // Datos del cónyuge/coacreditado
            'spouse_name' => $wizardData['spouse_name'] ?? '',
            'spouse_email' => $wizardData['spouse_email'] ?? '',
            'spouse_phone' => $wizardData['spouse_phone'] ?? '',
            'spouse_curp' => $wizardData['spouse_curp'] ?? '',
            'spouse_rfc' => $wizardData['spouse_rfc'] ?? '',
            'spouse_birthdate' => $wizardData['spouse_birthdate'] ?? '',
            'spouse_civil_status' => $wizardData['spouse_civil_status'] ?? '',
            'spouse_occupation' => $wizardData['spouse_occupation'] ?? '',
            'spouse_current_address' => $wizardData['spouse_current_address'] ?? '',
            'spouse_municipality' => $wizardData['spouse_municipality'] ?? '',
            'spouse_state' => $wizardData['spouse_state'] ?? '',
            'spouse_delivery_file' => $wizardData['spouse_delivery_file'] ?? '',
            'spouse_regime_type' => $wizardData['spouse_regime_type'] ?? '',
            'spouse_office_phone' => $wizardData['spouse_office_phone'] ?? '',
            'spouse_additional_contact_phone' => $wizardData['spouse_additional_contact_phone'] ?? '',
            'spouse_neighborhood' => $wizardData['spouse_neighborhood'] ?? '',
            'spouse_postal_code' => $wizardData['spouse_postal_code'] ?? '',

            // Datos de contacto AC y Presidente de Privada
            'ac_name' => $wizardData['ac_name'] ?? '',
            'ac_phone' => $wizardData['ac_phone'] ?? '',
            'ac_quota' => $wizardData['ac_quota'] ?? '',
            'private_president_name' => $wizardData['private_president_name'] ?? '',
            'private_president_phone' => $wizardData['private_president_phone'] ?? '',
            'private_president_quota' => $wizardData['private_president_quota'] ?? '',

            // Datos de la propiedad
            'domicilio_convenio' => $wizardData['domicilio_convenio'] ?? $wizardData['property_address'] ?? '',
            'comunidad' => $wizardData['comunidad'] ?? $wizardData['community'] ?? '',
            'property_community' => $wizardData['comunidad'] ?? $wizardData['community'] ?? '',
            'property_full_community' => $wizardData['comunidad'] ?? $wizardData['community'] ?? '',
            'tipo_vivienda' => $wizardData['tipo_vivienda'] ?? $wizardData['housing_type'] ?? '',
            'prototipo' => $wizardData['prototipo'] ?? $wizardData['prototype'] ?? '',
            'lote' => $wizardData['lote'] ?? $wizardData['lot'] ?? '',
            'manzana' => $wizardData['manzana'] ?? $wizardData['block'] ?? '',
            'etapa' => $wizardData['etapa'] ?? $wizardData['stage'] ?? '',
            'municipio_propiedad' => $wizardData['municipio_propiedad'] ?? $wizardData['property_municipality'] ?? '',
            'estado_propiedad' => $wizardData['estado_propiedad'] ?? $wizardData['property_state'] ?? '',
            'numero_interior' => $wizardData['numero_interior'] ?? $wizardData['interior_number'] ?? '',
            'property_interior_number' => $wizardData['numero_interior'] ?? $wizardData['interior_number'] ?? '',
            'property_lot' => $wizardData['lote'] ?? $wizardData['lot'] ?? '',
            'property_block' => $wizardData['manzana'] ?? $wizardData['block'] ?? '',
            'property_stage' => $wizardData['etapa'] ?? $wizardData['stage'] ?? '',

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
            'xante_id' => $agreement->client_xante_id ?? $wizardData['xante_id'] ?? '',

            // Datos bancarios (usar cuenta seleccionada o buscar por estado)
            'bank_name' => $this->getBankData($wizardData, 'bank_name'),
            'bank_account' => $this->getBankData($wizardData, 'account_number'),
            'bank_clabe' => $this->getBankData($wizardData, 'clabe'),

            // Imágenes en formato base64 para PDFs
            'logo_path' => $this->getImageBase64('Logo-Xante.png'),
            'logo_base64' => $this->getImageBase64('Logo-Xante.png'),

            // Imágenes de condiciones comercialización en base64
            'image_1_path' => $this->getImageBase64('1.png'),
            'image_2_path' => $this->getImageBase64('2.png'),
            'image_3_path' => $this->getImageBase64('3.png'),
            'image_4_path' => $this->getImageBase64('4.png'),
            'image_5_path' => $this->getImageBase64('5.png'),
            'image_6_path' => $this->getImageBase64('6.png'),
            'image_7_path' => $this->getImageBase64('7.png'),
            'image_8_path' => $this->getImageBase64('8.png'),

            // Nuevas variables estructuradas para 3 secciones
            'participants' => $this->getParticipants($agreement, $wizardData),
            'signers' => $this->getSigners($agreement, $wizardData),
        ];
    }

    /**
     * Determina la lista de participantes para mostrar en Datos Generales
     */
    private function getParticipants(Agreement $agreement, array $data): array
    {
        $participants = [];

        // 1. Titular (Siempre)
        $participants[] = [
            'role' => 'TITULAR',
            'title' => 'DATOS PERSONALES TITULAR:',
            'name' => $data['holder_name'] ?? '',
            'delivery_file' => $data['holder_delivery_file'] ?? '',
            'birthdate' => $data['holder_birthdate'] ?? '',
            'civil_status' => $data['holder_civil_status'] ?? '',
            'curp' => $data['holder_curp'] ?? '',
            'rfc' => $data['holder_rfc'] ?? '',
            'regime_type' => $data['holder_regime_type'] ?? '',
            'occupation' => $data['holder_occupation'] ?? '',
            'email' => $data['holder_email'] ?? '',
            'phone' => $data['holder_phone'] ?? '',
            'office_phone' => $data['holder_office_phone'] ?? '',
            'additional_contact_phone' => $data['holder_additional_contact_phone'] ?? '',
            'address' => $data['current_address'] ?? $data['holder_current_address'] ?? '',
            'neighborhood' => $data['neighborhood'] ?? $data['holder_neighborhood'] ?? '',
            'postal_code' => $data['postal_code'] ?? $data['holder_postal_code'] ?? '',
            'municipality' => $data['municipality'] ?? $data['holder_municipality'] ?? '',
            'state' => $data['state'] ?? $data['holder_state'] ?? '',
        ];

        // 2. Cónyuge (Si casado)
        if (($data['holder_civil_status'] ?? '') === 'casado') {
            $participants[] = [
                'role' => 'CÓNYUGE',
                'title' => 'DATOS DEL CÓNYUGE:',
                'name' => $data['spouse_name'] ?? '',
                'delivery_file' => $data['spouse_delivery_file'] ?? '',
                'birthdate' => $data['spouse_birthdate'] ?? '',
                'civil_status' => $data['spouse_civil_status'] ?? 'Casado(a)', // Asumido
                'curp' => $data['spouse_curp'] ?? '',
                'rfc' => $data['spouse_rfc'] ?? '',
                'regime_type' => $data['spouse_regime_type'] ?? '',
                'occupation' => $data['spouse_occupation'] ?? '',
                'email' => $data['spouse_email'] ?? '',
                'phone' => $data['spouse_phone'] ?? '',
                'office_phone' => $data['spouse_office_phone'] ?? '',
                'additional_contact_phone' => $data['spouse_additional_contact_phone'] ?? '',
                'address' => $data['spouse_current_address'] ?? '',
                'neighborhood' => $data['spouse_neighborhood'] ?? '',
                'postal_code' => $data['spouse_postal_code'] ?? '',
                'municipality' => $data['spouse_municipality'] ?? '',
                'state' => $data['spouse_state'] ?? '',
            ];
        }

        // 3. Coacreditado (Si existe y es TERCERO)
        // Nota: Si es Cónyuge, ya está arriba.
        if (! empty($data['has_co_borrower']) && ($data['co_borrower_relationship'] ?? '') === 'coacreditado') {
            $participants[] = [
                'role' => 'COACREDITADO',
                'title' => 'DATOS DEL COACREDITADO (TERCERO):',
                'name' => $data['co_borrower_name'] ?? '',
                'delivery_file' => '', // No capturado para tercero en wizard actual
                'birthdate' => $data['co_borrower_birthdate'] ?? '',
                'civil_status' => $data['co_borrower_civil_status'] ?? '',
                'curp' => $data['co_borrower_curp'] ?? '',
                'rfc' => $data['co_borrower_rfc'] ?? '',
                'regime_type' => '', // No capturado explícitamente como regimen fiscal, tal vez?
                'occupation' => $data['co_borrower_occupation'] ?? '',
                'email' => $data['co_borrower_email'] ?? '',
                'phone' => $data['co_borrower_phone'] ?? '',
                'office_phone' => '',
                'additional_contact_phone' => '',
                'address' => $data['co_borrower_current_address'] ?? '',
                'neighborhood' => '', // Simplificado en wizard
                'postal_code' => '',
                'municipality' => '',
                'state' => '',
            ];
        }

        return $participants;
    }

    /**
     * Determina quiénes deben firmar
     */
    private function getSigners(Agreement $agreement, array $data): array
    {
        $signers = [];

        // 1. Titular (Siempre)
        $signers[] = [
            'name' => strtoupper($data['holder_name'] ?? ''),
            'label' => 'EL VENDEDOR (TITULAR)',
        ];

        // Flag para saber si el cónyuge ya fue agregado
        $spouseAdded = false;

        // 2. Cónyuge (Solo si Bienes Mancomunados)
        if (($data['holder_civil_status'] ?? '') === 'casado' &&
            ($data['holder_marital_regime'] ?? '') === 'bienes_mancomunados') {
            $signers[] = [
                'name' => strtoupper($data['spouse_name'] ?? ''),
                'label' => 'EL CÓNYUGE',
            ];
            $spouseAdded = true;
        }

        // 3. Coacreditado (Si existe)
        if (! empty($data['has_co_borrower'])) {
            $type = $data['co_borrower_relationship'] ?? '';

            if ($type === 'cónyuge') {
                // Si el cónyuge es coacreditado y NO ha sido agregado (ej. Bienes Separados)
                if (! $spouseAdded) {
                    $signers[] = [
                        'name' => strtoupper($data['spouse_name'] ?? ''),
                        'label' => 'COACREDITADO (CÓNYUGE)',
                    ];
                } else {
                    // Si ya estaba (Mancomunados), actualizamos la etiqueta
                    // Buscar el índice del cónyuge (debería ser el último agregado)
                    $lastIndex = count($signers) - 1;
                    $signers[$lastIndex]['label'] = 'CÓNYUGE Y COACREDITADO';
                }
            } elseif ($type === 'coacreditado') {
                // Tercero: Siempre se agrega
                $signers[] = [
                    'name' => strtoupper($data['co_borrower_name'] ?? ''),
                    'label' => 'COACREDITADO (TERCERO)',
                ];
            }
        }

        return $signers;
    }

    /**
     * Convierte una imagen a formato base64 para usar en PDFs, con fallbacks.
     */
    private function getImageBase64(string $filename): string
    {
        $imagePath = resource_path("views/pdfs/images/{$filename}");

        if (! file_exists($imagePath)) {
            Log::warning("Imagen no encontrada: {$imagePath}");

            return '';
        }

        try {
            $imageData = file_get_contents($imagePath);
            $mimeType = '';

            // Opción 1: Usar mime_content_type si existe (extensión fileinfo)
            if (function_exists('mime_content_type')) {
                $mimeType = mime_content_type($imagePath);
            }
            // Opción 2: Fallback a getimagesize si la primera falla (extensión GD)
            elseif (function_exists('getimagesize')) {
                $imageInfo = getimagesize($imagePath);
                if ($imageInfo && isset($imageInfo['mime'])) {
                    $mimeType = $imageInfo['mime'];
                }
            }

            // Opción 3: Fallback a la extensión del archivo si todo lo demás falla
            if (empty($mimeType)) {
                $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                ];
                if (isset($mimeTypes[$extension])) {
                    $mimeType = $mimeTypes[$extension];
                } else {
                    // Usar un tipo genérico si la extensión no se reconoce
                    $mimeType = 'application/octet-stream';
                }
            }

            $base64 = "data:{$mimeType};base64,".base64_encode($imageData);

            Log::info('Imagen convertida a base64', [
                'filename' => $filename,
                'path' => $imagePath,
                'mime_type' => $mimeType,
                'size' => strlen($imageData),
            ]);

            return $base64;
        } catch (\Exception $e) {
            Log::error("Error al convertir imagen a base64: {$filename}", ['error' => $e->getMessage()]);

            return '';
        }
    }

    /**
     * Obtiene datos bancarios usando el bank_account_id seleccionado o por estado
     */
    private function getBankData(array $wizardData, string $field): string
    {
        // Prioridad 1: Usar bank_account_id si fue seleccionado en el wizard
        if (! empty($wizardData['bank_account_id'])) {
            $bankAccount = \App\Models\StateBankAccount::find($wizardData['bank_account_id']);

            if ($bankAccount) {
                Log::info('Usando cuenta bancaria seleccionada en wizard', [
                    'bank_account_id' => $wizardData['bank_account_id'],
                    'bank_name' => $bankAccount->bank_name,
                    'municipality' => $bankAccount->municipality,
                ]);

                return $bankAccount->{$field} ?? $this->getDefaultBankData($field);
            }
        }

        // Prioridad 2: Fallback a búsqueda por estado (para convenios antiguos)
        if (! empty($wizardData['estado_propiedad'])) {
            Log::info('Fallback: Buscando cuenta bancaria por estado', [
                'estado_propiedad' => $wizardData['estado_propiedad'],
            ]);

            return $this->getBankDataForState($wizardData['estado_propiedad'], $field);
        }

        // Prioridad 3: Valores por defecto
        Log::warning('No se encontró bank_account_id ni estado_propiedad, usando valores por defecto');

        return $this->getDefaultBankData($field);
    }

    /**
     * Obtiene datos bancarios específicos del estado
     */
    private function getBankDataForState(?string $stateName, string $field): string
    {
        if (! $stateName) {
            return $this->getDefaultBankData($field);
        }

        $bankAccount = \App\Models\StateBankAccount::where('state_name', $stateName)->first();

        if (! $bankAccount) {
            Log::warning("No se encontró cuenta bancaria para el estado: {$stateName}");

            return $this->getDefaultBankData($field);
        }

        return $bankAccount->{$field} ?? $this->getDefaultBankData($field);
    }

    /**
     * Retorna valores por defecto para datos bancarios
     */
    private function getDefaultBankData(string $field): string
    {
        $defaults = [
            'bank_name' => 'BBVA',
            'account_number' => '0123456789',
            'clabe' => '012345678901234567',
        ];

        return $defaults[$field] ?? '';
    }

    /**
     * Convierte números a palabras (implementación básica)
     */
    private function numberToWords(float $number): string
    {
        try {
            if (class_exists('\NumberFormatter')) {
                $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
                $words = $formatter->format($number);

                // Reemplazar "coma" por "punto" para decimales (6.5 = "seis punto cinco" no "seis coma cinco")
                $words = str_replace(' coma ', ' punto ', $words);

                return $words;
            }
        } catch (\Exception $e) {
            Log::warning('NumberFormatter no disponible: '.$e->getMessage());
        }

        // Fallback: implementación básica para números comunes
        $number = round($number, 1);

        if ($number == 6.5) {
            return 'seis punto cinco';
        }
        if ($number == 7.0) {
            return 'siete';
        }
        if ($number == 7.5) {
            return 'siete punto cinco';
        }
        if ($number == 8.0) {
            return 'ocho';
        }
        if ($number == 8.5) {
            return 'ocho punto cinco';
        }
        if ($number == 9.0) {
            return 'nueve';
        }
        if ($number == 9.5) {
            return 'nueve punto cinco';
        }

        // Para otros números, usar formato simple
        return str_replace('.', ' punto ', number_format($number, 1, '.', ''));
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
        // Lista completa de los 6 documentos esperados
        $expectedTypes = [
            // 4 plantillas Blade
            'acuerdo_promocion',
            'datos_generales',
            'checklist_expediente',
            'condiciones_comercializacion',
            // 2 documentos originales
            'aviso_privacidad',
            'euc_venta_convenio',
        ];
        $generatedTypes = $agreement->generatedDocuments()->pluck('document_type')->toArray();

        foreach ($expectedTypes as $type) {
            if (! in_array($type, $generatedTypes)) {
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
     * Genera el checklist de documentos con marcado dinámico
     */
    public function generateChecklist(Agreement $agreement, array $uploadedDocuments = [], bool $isUpdated = false)
    {
        try {
            // Preparar datos para la plantilla
            $data = $this->prepareTemplateData($agreement);

            // Agregar datos específicos para el checklist actualizado
            $data['uploadedDocuments'] = $uploadedDocuments;
            $data['isUpdated'] = $isUpdated;

            Log::info('Generando checklist dinámico', [
                'agreement_id' => $agreement->id,
                'is_updated' => $isUpdated,
                'uploaded_documents_count' => count($uploadedDocuments),
                'uploaded_documents' => $uploadedDocuments,
            ]);

            // Verificar que la vista existe
            $viewPath = 'pdfs.templates.checklist_expediente';
            if (! view()->exists($viewPath)) {
                throw new \Exception("La plantilla Blade no existe: {$viewPath}");
            }

            // Renderizar HTML desde Blade
            $html = view($viewPath, $data)->render();

            Log::info('HTML del checklist renderizado exitosamente', [
                'agreement_id' => $agreement->id,
                'html_length' => strlen($html),
            ]);

            // Configurar PDF
            $pdf = Pdf::loadHTML($html)
                ->setPaper('letter')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                ]);

            Log::info('PDF del checklist generado exitosamente', [
                'agreement_id' => $agreement->id,
                'is_updated' => $isUpdated,
            ]);

            return $pdf;

        } catch (\Exception $e) {
            Log::error("Error generando checklist dinámico para Agreement #{$agreement->id}: ".$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'is_updated' => $isUpdated,
                'uploaded_documents' => $uploadedDocuments,
            ]);
            throw $e;
        }
    }

    /**
     * Elimina todos los documentos generados (solo para casos de error)
     */
    public function cleanupGeneratedDocuments(Agreement $agreement): void
    {
        $documents = $agreement->generatedDocuments;

        foreach ($documents as $document) {
            if (Storage::disk('s3')->exists($document->file_path)) {
                Storage::disk('s3')->delete($document->file_path);
            }

            // Eliminar registro de BD
            $document->delete();
        }

        Log::info("Documentos limpiados para Agreement #{$agreement->id}");
    }
}
