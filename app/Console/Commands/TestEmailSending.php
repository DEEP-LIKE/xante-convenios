<?php

namespace App\Console\Commands;

use App\Mail\DocumentsReadyMail;
use App\Models\Agreement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailSending extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:email-sending {agreement_id} {--email=}';

    /**
     * The console command description.
     */
    protected $description = 'Test email sending functionality for document delivery';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agreementId = $this->argument('agreement_id');
        $testEmail = $this->option('email');

        try {
            // Buscar el convenio
            $agreement = Agreement::with('generatedDocuments')->find($agreementId);

            if (! $agreement) {
                $this->error("❌ Convenio con ID {$agreementId} no encontrado");

                return 1;
            }

            $this->info("📋 Convenio encontrado: ID {$agreement->id}");

            // Verificar documentos generados
            if ($agreement->generatedDocuments->isEmpty()) {
                $this->error('❌ No hay documentos generados para este convenio');

                return 1;
            }

            $this->info('📄 Documentos encontrados: '.$agreement->generatedDocuments->count());

            // Verificar que los archivos existen
            $existingFiles = $agreement->generatedDocuments->filter(function ($document) {
                return $document->fileExists();
            });

            $this->info('✅ Archivos físicos existentes: '.$existingFiles->count());

            if ($existingFiles->isEmpty()) {
                $this->error('❌ No se encontraron archivos PDF en el servidor');

                return 1;
            }

            // Listar archivos
            foreach ($existingFiles as $document) {
                $this->line("  - {$document->document_name} ({$document->formatted_size})");
            }

            // Determinar email de destino
            $clientEmail = null;
            if ($testEmail) {
                $clientEmail = $testEmail;
                $this->info("📧 Usando email de prueba: {$clientEmail}");
            } else {
                $wizardData = $agreement->wizard_data ?? [];
                $clientEmail = $wizardData['holder_email'] ?? null;

                if (! $clientEmail) {
                    $this->error('❌ No se encontró email del cliente en wizard_data');
                    $this->info('💡 Use --email=tu@email.com para especificar un email de prueba');

                    return 1;
                }

                $this->info("📧 Usando email del cliente: {$clientEmail}");
            }

            // Confirmar envío
            if (! $this->confirm("¿Desea enviar el correo de prueba a {$clientEmail}?")) {
                $this->info('❌ Envío cancelado por el usuario');

                return 0;
            }

            // Enviar correo
            $this->info('📤 Enviando correo...');

            Mail::to($clientEmail)->send(new DocumentsReadyMail($agreement));

            $this->info('✅ Correo enviado exitosamente!');
            $this->info('📊 Resumen del envío:');
            $this->line("  - Destinatario: {$clientEmail}");
            $this->line("  - Documentos adjuntos: {$existingFiles->count()}");
            $this->line("  - Convenio ID: {$agreement->id}");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar correo: '.$e->getMessage());
            $this->error('🔍 Detalles técnicos: '.$e->getFile().':'.$e->getLine());

            return 1;
        }
    }
}
