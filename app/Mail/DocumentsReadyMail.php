<?php

namespace App\Mail;

use App\Models\Agreement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentsReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    protected array $tempFiles = [];

    public function __construct(
        public Agreement $agreement
    ) {}

    public function __destruct()
    {
        // Limpiar archivos temporales después de enviar el email
        foreach ($this->tempFiles as $tempPath) {
            try {
                if (\Storage::disk('local')->exists($tempPath)) {
                    \Storage::disk('local')->delete($tempPath);
                    \Log::debug('Temporary attachment file deleted', ['path' => $tempPath]);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to delete temporary attachment file', [
                    'path' => $tempPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function envelope(): Envelope
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        $clientName = $wizardData['holder_name'] ?? 'Cliente';

        return new Envelope(
            subject: "Documentos de su Convenio Inmobiliario - {$clientName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.documents-ready',
            with: [
                'agreement' => $this->agreement,
                'clientName' => $this->agreement->wizard_data['holder_name'] ?? 'Cliente',
                'propertyAddress' => $this->agreement->wizard_data['domicilio_convenio'] ?? 'N/A',
                'valorConvenio' => number_format(
                    floatval(str_replace(',', '', $this->agreement->wizard_data['valor_convenio'] ?? 0)),
                    2
                ),
                'gananciaFinal' => number_format(
                    floatval(str_replace(',', '', $this->agreement->wizard_data['ganancia_final'] ?? 0)),
                    2
                ),
            ]
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        try {
            \Log::info('DocumentsReadyMail: Starting attachment process', [
                'agreement_id' => $this->agreement->id,
                'documents_count' => $this->agreement->generatedDocuments->count(),
            ]);

            // Adjuntar documentos PDF generados (solo archivos menores a 4MB)
            $maxFileSize = 4 * 1024 * 1024; // 4MB en bytes
            $totalSize = 0;
            $attachedCount = 0;

            foreach ($this->agreement->generatedDocuments as $document) {
                \Log::debug('Processing document for attachment', [
                    'document_id' => $document->id,
                    'document_name' => $document->document_name,
                    'file_path' => $document->file_path,
                ]);

                if (!$document->fileExists()) {
                    \Log::warning('Document file does not exist in S3', [
                        'document_id' => $document->id,
                        'file_path' => $document->file_path,
                    ]);
                    continue;
                }

                try {
                    // Verificar tamaño del archivo en S3
                    $fileSize = \Storage::disk('s3')->size($document->file_path);
                    
                    \Log::debug('Document file size checked', [
                        'document_id' => $document->id,
                        'file_size' => $fileSize,
                        'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                    ]);

                    // Solo adjuntar si el archivo es menor a 4MB y el total no excede 4MB
                    if ($fileSize > 0 && $fileSize < $maxFileSize && ($totalSize + $fileSize) < $maxFileSize) {
                        try {
                            // SOLUCIÓN: Descargar archivo de S3 a almacenamiento temporal
                            $tempDir = 'temp/email_attachments';
                            $fullTempDir = storage_path('app/' . $tempDir);
                            
                            // Asegurar que el directorio existe usando mkdir nativo
                            if (!file_exists($fullTempDir)) {
                                mkdir($fullTempDir, 0755, true);
                                \Log::debug('Created temp directory', ['dir' => $fullTempDir]);
                            }
                            
                            // Sanitizar nombre de archivo
                            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $document->document_name);
                            $fileName = uniqid() . '_' . $safeName . '.pdf';
                            $tempPath = $tempDir . '/' . $fileName;
                            
                            // Copiar de S3 a disco local temporal
                            $fileContent = \Storage::disk('s3')->get($document->file_path);
                            \Storage::disk('local')->put($tempPath, $fileContent);
                            
                            // Registrar para limpieza posterior
                            $this->tempFiles[] = $tempPath;
                            
                            $localPath = storage_path('app/' . $tempPath);
                            
                            \Log::info('Document downloaded from S3 to temp storage', [
                                'document_id' => $document->id,
                                's3_path' => $document->file_path,
                                'temp_path' => $tempPath,
                                'local_path' => $localPath,
                                'exists' => file_exists($localPath),
                                'readable' => is_readable($localPath),
                            ]);

                            // Adjuntar desde el archivo temporal local
                            $attachments[] = Attachment::fromPath($localPath)
                                ->as($document->document_name . '.pdf')
                                ->withMime('application/pdf');
                            
                            $totalSize += $fileSize;
                            $attachedCount++;
                            
                            \Log::info('Document attached successfully', [
                                'document_id' => $document->id,
                                'document_name' => $document->document_name,
                                'attached_count' => $attachedCount,
                            ]);
                        } catch (\Exception $e) {
                            \Log::error('Error attaching document', [
                                'document_id' => $document->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                        }
                    } else {
                        \Log::warning('Skipping document - size limit exceeded', [
                            'document_id' => $document->id,
                            'document_name' => $document->document_name,
                            'file_size' => $fileSize,
                            'total_size' => $totalSize,
                            'max_file_size' => $maxFileSize,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error processing document attachment', [
                        'document_id' => $document->id,
                        'document_name' => $document->document_name,
                        'file_path' => $document->file_path,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Adjuntar imagen de oferta desde public/images/oferta.jpg
            $ofertaImagePath = public_path('images/oferta.jpg');
            if (file_exists($ofertaImagePath)) {
                $attachments[] = Attachment::fromPath($ofertaImagePath)
                    ->as('Oferta_Especial_Xante.jpg')
                    ->withMime('image/jpeg');
                
                \Log::debug('Oferta image attached', ['path' => $ofertaImagePath]);
            } else {
                \Log::warning('Oferta image not found', ['path' => $ofertaImagePath]);
            }

            \Log::info('DocumentsReadyMail: Attachment process completed', [
                'agreement_id' => $this->agreement->id,
                'total_attachments' => count($attachments),
                'pdf_attachments' => $attachedCount,
            ]);

        } catch (\Exception $e) {
            \Log::error('Critical error in attachments() method', [
                'agreement_id' => $this->agreement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $attachments;
    }
}
