<?php

namespace Tests\Unit;

use App\Mail\AgreementMail;
use App\Mail\DocumentsReadyMail;
use App\Mail\DocumentsReceivedConfirmationMail;
use App\Models\Agreement;
use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class MailableEnvelopeTest extends TestCase
{
    public function test_documents_ready_mail_envelope_has_convenios_from_and_reply_to(): void
    {
        $agreement = new Agreement();
        $agreement->wizard_data = [
            'holder_name' => 'Juan Perez',
            'domicilio_convenio' => 'Privada Real Castilla',
            'valor_convenio' => '1,200,000.00',
        ];

        $mail = new DocumentsReadyMail($agreement);
        $envelope = $mail->envelope();

        $this->assertEquals('convenios@xante.mx', $envelope->from->address);
        $this->assertEquals('Xante Convenios', $envelope->from->name);
        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals('convenios@xante.mx', $envelope->replyTo[0]->address);
        $this->assertEquals('Xante Convenios', $envelope->replyTo[0]->name);
        $this->assertStringContainsString('Juan Perez', $envelope->subject);
        $this->assertStringContainsString('Privada Real Castilla', $envelope->subject);

        $content = $mail->content();
        $this->assertEquals('Juan Perez', $content->with['clientName']);
        $this->assertEquals('Privada Real Castilla', $content->with['propertyAddress']);
        $this->assertEquals('1,200,000.00', $content->with['valorConvenio']);
    }

    public function test_documents_received_confirmation_mail_envelope_has_convenios_from_and_reply_to(): void
    {
        $agreement = new Agreement();
        $clientDocuments = new Collection();

        $mail = new DocumentsReceivedConfirmationMail($agreement, $clientDocuments);
        $envelope = $mail->envelope();

        $this->assertEquals('convenios@xante.mx', $envelope->from->address);
        $this->assertEquals('Xante Convenios', $envelope->from->name);
        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals('convenios@xante.mx', $envelope->replyTo[0]->address);
        $this->assertEquals('Xante Convenios', $envelope->replyTo[0]->name);
    }

    public function test_agreement_mail_envelope_has_convenios_from_and_reply_to(): void
    {
        $agreement = new Agreement();
        $client = new Client();
        $client->name = 'Maria Lopez';
        $agreement->setRelation('client', $client);

        $mail = new AgreementMail($agreement, 'dummy/path.pdf');
        $envelope = $mail->envelope();

        $this->assertEquals('convenios@xante.mx', $envelope->from->address);
        $this->assertEquals('Xante Convenios', $envelope->from->name);
        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals('convenios@xante.mx', $envelope->replyTo[0]->address);
        $this->assertEquals('Xante Convenios', $envelope->replyTo[0]->name);
        $this->assertStringContainsString('Maria Lopez', $envelope->subject);
    }
}
