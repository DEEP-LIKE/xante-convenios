<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agreement;
use App\Models\ClientDocument;
use App\Mail\DocumentsReceivedConfirmationMail;
use Illuminate\Support\Facades\Mail;

class TestConfirmationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:confirmation-email {agreement_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the documents received confirmation email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agreementId = $this->argument('agreement_id');
        
        $agreement = Agreement::find($agreementId);
        if (!$agreement) {
            $this->error("Agreement with ID {$agreementId} not found");
            return 1;
        }
        
        $clientDocuments = ClientDocument::where('agreement_id', $agreementId)->get();
        
        $this->info("Testing confirmation email for agreement {$agreementId}");
        $this->info("Client documents found: " . $clientDocuments->count());
        
        // Mostrar información de cada documento
        foreach ($clientDocuments as $doc) {
            $this->info("Document: {$doc->document_name} | Type: {$doc->document_type} | File: {$doc->file_name}");
        }
        
        try {
            // Obtener email del cliente desde wizard_data
            $clientEmail = 'test@example.com'; // Email de prueba
            if ($agreement->wizard_data && isset($agreement->wizard_data['client_email'])) {
                $clientEmail = $agreement->wizard_data['client_email'];
            }
            
            $this->info("Sending to: {$clientEmail}");
            
            Mail::to($clientEmail)
                ->send(new DocumentsReceivedConfirmationMail($agreement, $clientDocuments));
                
            $this->info("✅ Email sent successfully!");
            
        } catch (\Exception $e) {
            $this->error("❌ Error sending email: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
