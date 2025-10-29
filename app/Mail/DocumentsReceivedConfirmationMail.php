<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Agreement;
use Illuminate\Database\Eloquent\Collection;

class DocumentsReceivedConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Agreement $agreement;
    public Collection $clientDocuments;

    /**
     * Create a new message instance.
     */
    public function __construct(Agreement $agreement, Collection $clientDocuments)
    {
        $this->agreement = $agreement;
        $this->clientDocuments = $clientDocuments;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Convenio Completado - Documentos Recibidos Satisfactoriamente',
            from: 'hello@xante.mx',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.documents-received-confirmation',
            with: [
                'agreement' => $this->agreement,
                'clientDocuments' => $this->clientDocuments,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
