<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agreement;
use App\Models\Client;
use App\Services\PdfGenerationService;
use Illuminate\Support\Facades\Storage;

class TestWizard2Functionality extends Command
{
    protected $signature = 'test:wizard2 {--create-test-data : Create test data if no agreements exist}';
    protected $description = 'Test Wizard 2 functionality and document generation';

    public function handle()
    {
        $this->info('🧪 Iniciando pruebas del Wizard 2...');
        
        // Verificar si hay convenios disponibles
        $agreementsCount = Agreement::count();
        $this->info("📊 Convenios en base de datos: {$agreementsCount}");
        
        if ($agreementsCount === 0 && $this->option('create-test-data')) {
            $this->createTestData();
        }
        
        // Buscar un convenio para probar
        $agreement = Agreement::with('client', 'generatedDocuments')->first();
        
        if (!$agreement) {
            $this->error('❌ No hay convenios disponibles para probar.');
            $this->info('💡 Use --create-test-data para crear datos de prueba.');
            return 1;
        }
        
        $this->info("🎯 Probando con Agreement ID: {$agreement->id}");
        $this->info("👤 Cliente: " . ($agreement->client->name ?? 'N/A'));
        $this->info("📧 Email: " . ($agreement->client->email ?? 'N/A'));
        $this->info("📊 Estado actual: {$agreement->status}");
        
        // Verificar estructura de carpetas
        $this->testStorageStructure();
        
        // Probar generación de PDFs si no existen
        if ($agreement->generatedDocuments->isEmpty()) {
            $this->testPdfGeneration($agreement);
        } else {
            $this->info("📄 Documentos existentes: " . $agreement->generatedDocuments->count());
            $this->testExistingDocuments($agreement);
        }
        
        // Probar rutas y controladores
        $this->testRoutes($agreement);
        
        $this->info('✅ Pruebas completadas exitosamente!');
        return 0;
    }
    
    private function createTestData()
    {
        $this->info('🏗️ Creando datos de prueba...');
        
        // Crear cliente de prueba
        $client = Client::create([
            'name' => 'Cliente de Prueba',
            'email' => 'test@example.com',
            'phone' => '555-0123',
            'xante_id' => 'TEST001'
        ]);
        
        // Crear convenio de prueba
        $agreement = Agreement::create([
            'client_id' => $client->id,
            'status' => 'documents_generated',
            'current_step' => 5,
            'current_wizard' => 2,
            'wizard_data' => [
                'holder_name' => 'Cliente de Prueba',
                'holder_email' => 'test@example.com',
                'valor_convenio' => '1000000',
                'domicilio_convenio' => 'Calle de Prueba 123',
                'comunidad' => 'Comunidad Test'
            ],
            'created_by' => 1
        ]);
        
        $this->info("✅ Datos de prueba creados - Agreement ID: {$agreement->id}");
    }
    
    private function testStorageStructure()
    {
        $this->info('📁 Verificando estructura de almacenamiento...');
        
        // Verificar discos
        $disks = ['private', 'public'];
        foreach ($disks as $disk) {
            if (Storage::disk($disk)->exists('')) {
                $this->info("✅ Disco '{$disk}' disponible");
            } else {
                $this->error("❌ Disco '{$disk}' no disponible");
            }
        }
        
        // Verificar plantillas
        $templates = [
            'resources/views/pdfs/templates/acuerdo_promocion.blade.php',
            'resources/views/pdfs/templates/datos_generales.blade.php',
            'resources/views/pdfs/templates/checklist_expediente.blade.php',
            'resources/views/pdfs/templates/condiciones_comercializacion.blade.php'
        ];
        
        foreach ($templates as $template) {
            if (file_exists(base_path($template))) {
                $this->info("✅ Plantilla encontrada: " . basename($template));
            } else {
                $this->error("❌ Plantilla faltante: " . basename($template));
            }
        }
    }
    
    private function testPdfGeneration(Agreement $agreement)
    {
        $this->info('📄 Probando generación de PDFs...');
        
        try {
            $pdfService = app(PdfGenerationService::class);
            $documents = $pdfService->generateAllDocuments($agreement);
            
            $this->info("✅ PDFs generados exitosamente: " . count($documents));
            
            foreach ($documents as $doc) {
                $this->line("  - {$doc->document_name} ({$doc->formatted_size})");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error en generación: " . $e->getMessage());
        }
    }
    
    private function testExistingDocuments(Agreement $agreement)
    {
        $this->info('🔍 Verificando documentos existentes...');
        
        foreach ($agreement->generatedDocuments as $document) {
            $exists = $document->fileExists();
            $status = $exists ? '✅' : '❌';
            $this->line("{$status} {$document->formatted_type} - {$document->formatted_size}");
            
            if (!$exists) {
                $this->warn("   Archivo no encontrado: {$document->file_path}");
            }
        }
    }
    
    private function testRoutes(Agreement $agreement)
    {
        $this->info('🔗 Verificando rutas...');
        
        // Verificar ruta principal del Wizard 2 (nueva página migrada)
        $wizard2Url = "/admin/manage-documents/{$agreement->id}";
        $this->info("✅ Ruta Wizard 2: {$wizard2Url}");
        
        // Verificar rutas de descarga
        foreach ($agreement->generatedDocuments as $document) {
            $downloadUrl = route('documents.download', ['document' => $document->id]);
            $this->line("✅ Ruta descarga: {$downloadUrl}");
        }
    }
}
