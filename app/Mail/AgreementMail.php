<?php

namespace App\Mail;

use App\Models\Agreement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class AgreementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Agreement $agreement,
        public string $pdfPath
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Convenio de Compraventa - ' . $this->agreement->client->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agreement',
            with: [
                'agreement' => $this->agreement,
                'clientName' => $this->agreement->client->name,
                'propertyAddress' => $this->agreement->property->address,
                'totalPayment' => $this->agreement->calculation->total_payment,
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('convenio_' . str_pad($this->agreement->id, 6, '0', STR_PAD_LEFT) . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
