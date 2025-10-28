<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agreement;

class ListAgreements extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'list:agreements {--with-documents}';

    /**
     * The console command description.
     */
    protected $description = 'List agreements and their document status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $withDocuments = $this->option('with-documents');

        $this->info("📋 Listando convenios...");
        $this->newLine();

        $query = Agreement::query();
        
        if ($withDocuments) {
            $query->whereHas('generatedDocuments');
            $this->info("🔍 Mostrando solo convenios con documentos generados");
        }

        $agreements = $query->with(['generatedDocuments'])->get();

        if ($agreements->isEmpty()) {
            $this->warn("❌ No se encontraron convenios");
            return 0;
        }

        $this->info("📊 Total de convenios: " . $agreements->count());
        $this->newLine();

        foreach ($agreements as $agreement) {
            $documentsCount = $agreement->generatedDocuments->count();
            $status = $agreement->status ?? 'draft';
            
            $this->line("🆔 ID: {$agreement->id}");
            $this->line("📄 Documentos: {$documentsCount}");
            $this->line("📊 Estado: {$status}");
            
            // Mostrar email del cliente si existe
            $wizardData = $agreement->wizard_data ?? [];
            $clientEmail = $wizardData['holder_email'] ?? 'No disponible';
            $clientName = $wizardData['holder_name'] ?? 'No disponible';
            
            $this->line("👤 Cliente: {$clientName}");
            $this->line("📧 Email: {$clientEmail}");
            
            if ($documentsCount > 0) {
                $this->line("📁 Documentos generados:");
                foreach ($agreement->generatedDocuments as $doc) {
                    $exists = $doc->fileExists() ? '✅' : '❌';
                    $this->line("  {$exists} {$doc->document_name}");
                }
            }
            
            $this->line("─────────────────────────────────────");
        }

        if ($agreements->where('generatedDocuments.count', '>', 0)->count() > 0) {
            $this->newLine();
            $this->info("🧪 Para probar el envío de correos, usa:");
            $this->line("php artisan test:email-sending {agreement_id} --email=tu@email.com");
        }

        return 0;
    }
}
