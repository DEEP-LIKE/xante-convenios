<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agreement;
use App\Services\PdfGenerationService;

class TestPdfGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:pdf-generation {agreement_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PDF generation for a specific agreement';

    /**
     * Execute the console command.
     */
    public function handle(PdfGenerationService $pdfService)
    {
        $agreementId = $this->argument('agreement_id');
        
        $this->info("Buscando Agreement ID: {$agreementId}");
        
        $agreement = Agreement::find($agreementId);
        
        if (!$agreement) {
            $this->error("Agreement {$agreementId} no encontrado");
            return 1;
        }
        
        $this->info("Agreement encontrado: {$agreement->id}");
        $this->info("Status actual: {$agreement->status}");
        $this->info("Wizard data: " . (empty($agreement->wizard_data) ? 'VACÍO' : 'PRESENTE'));
        
        try {
            $this->info("Iniciando generación de PDFs...");
            $documents = $pdfService->generateAllDocuments($agreement);
            
            $this->info("✅ Generación exitosa!");
            $this->info("Documentos generados: " . count($documents));
            
            foreach ($documents as $doc) {
                $this->line("- {$doc->document_name} ({$doc->formatted_size})");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error en generación: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
