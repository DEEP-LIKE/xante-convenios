<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agreement;
use App\Models\ClientDocument;
use App\Services\PdfGenerationService;

class TestDownloadButton extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:download-button {agreement_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la funcionalidad del botón de descarga de checklist actualizado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agreementId = $this->argument('agreement_id');
        
        $this->info("🧪 Probando funcionalidad del botón de descarga para Agreement ID: {$agreementId}");
        
        // Verificar que el convenio existe
        $agreement = Agreement::find($agreementId);
        if (!$agreement) {
            $this->error("❌ No se encontró el convenio con ID: {$agreementId}");
            return 1;
        }
        
        $this->info("✅ Convenio encontrado: {$agreement->client->name}");
        
        // Verificar documentos cargados
        $uploadedDocuments = ClientDocument::where('agreement_id', $agreement->id)
            ->pluck('document_type')
            ->toArray();
            
        $this->info("📄 Documentos cargados: " . count($uploadedDocuments));
        foreach ($uploadedDocuments as $doc) {
            $this->line("  - {$doc}");
        }
        
        // Probar el servicio PDF
        try {
            $pdfService = app(PdfGenerationService::class);
            
            $this->info("🔄 Generando PDF de prueba...");
            $pdf = $pdfService->generateChecklist(
                $agreement,
                $uploadedDocuments,
                true
            );
            
            $this->info("✅ PDF generado exitosamente");
            $this->info("📊 Tamaño del PDF: " . strlen($pdf->output()) . " bytes");
            
            // Verificar que la vista existe
            if (view()->exists('pdfs.templates.checklist_expediente')) {
                $this->info("✅ Vista checklist_expediente existe");
            } else {
                $this->error("❌ Vista checklist_expediente NO existe");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error al generar PDF: " . $e->getMessage());
            $this->error("📍 Archivo: " . $e->getFile() . ":" . $e->getLine());
            return 1;
        }
    }
}
