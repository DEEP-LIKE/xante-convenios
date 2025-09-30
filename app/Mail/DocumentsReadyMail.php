<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use App\Models\Agreement;

class DocumentsReadyMail extends Mailable implements ShouldQueue
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
        
        foreach ($this->agreement->generatedDocuments as $document) {
            if ($document->fileExists()) {
                $attachments[] = Attachment::fromStorageDisk('private', $document->file_path)
                    ->as($document->document_name . '.pdf')
                    ->withMime('application/pdf');
            }
        }
        
        return $attachments;
    }
}
