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

    public function __construct(
        public Agreement $agreement
    ) {}

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
            // Adjuntar documentos PDF generados (solo archivos menores a 4MB)
            $maxFileSize = 4 * 1024 * 1024; // 4MB en bytes
            $totalSize = 0;

            foreach ($this->agreement->generatedDocuments as $document) {
                if ($document->fileExists()) {
                    $fileSize = 0;
                    try {
                        $fileSize = \Storage::disk('s3')->size($document->file_path);
                    } catch (\Exception $e) {
                        \Log::warning('Error checking file size for attachment in email', [
                            'path' => $document->file_path,
                            'error' => $e->getMessage()
                        ]);
                        // Fallback to 0 to avoid crash, effectively skipping attachment if size cannot be determined
                    }

                    // Solo adjuntar si el archivo es menor a 4MB y el total no excede 4MB
                    if ($fileSize > 0 && $fileSize < $maxFileSize && ($totalSize + $fileSize) < $maxFileSize) {
                        $attachments[] = Attachment::fromStorageDisk('s3', $document->file_path)
                            ->as($document->document_name.'.pdf')
                            ->withMime('application/pdf');
                        $totalSize += $fileSize;
                    } else {
                        \Log::info('Skipping large or unreadable file attachment for email', [
                            'document' => $document->document_name,
                            'size' => $fileSize,
                            'max_size' => $maxFileSize,
                            'total_current_size' => $totalSize,
                        ]);
                    }
                }
            }

            // Adjuntar imagen de oferta desde public/images/oferta.jpg
            $ofertaImagePath = public_path('images/oferta.jpg');
            if (file_exists($ofertaImagePath)) {
                $attachments[] = Attachment::fromPath($ofertaImagePath)
                    ->as('Oferta_Especial_Xante.jpg')
                    ->withMime('image/jpeg');
            }
        } catch (\Exception $e) {
            // Log error but don't fail the email
            \Log::error('Error adding attachments to email', [
                'agreement_id' => $this->agreement->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $attachments;
    }
}
