<?php

namespace App\Console\Commands;

use App\Mail\DocumentsReadyMail;
use App\Models\Agreement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestDualEmailSending extends Command
{
    protected $signature = 'test:dual-email-sending {agreement_id} {--advisor-email=} {--client-email=}';

    protected $description = 'Test sending emails to both advisor and client';

    public function handle()
    {
        $agreementId = $this->argument('agreement_id');
        $advisorEmail = $this->option('advisor-email') ?? 'asesor@mailtrap.io';
        $clientEmail = $this->option('client-email') ?? 'cliente@mailtrap.io';

        // Buscar el convenio
        $agreement = Agreement::with('generatedDocuments')->find($agreementId);

        if (! $agreement) {
            $this->error("❌ Convenio con ID {$agreementId} no encontrado");

            return 1;
        }

        $this->info("📋 Convenio encontrado: ID {$agreement->id}");
        $this->info("📄 Documentos encontrados: {$agreement->generatedDocuments->count()}");

        // Verificar archivos físicos
        $documentsWithFiles = $agreement->generatedDocuments->filter(function ($document) {
            return $document->fileExists();
        });

        $this->info("✅ Archivos físicos existentes: {$documentsWithFiles->count()}");

        foreach ($documentsWithFiles as $document) {
            $size = $document->getFileSize();
            $this->line("  - {$document->formatted_type} ({$size})");
        }

        $this->info('📧 Enviando correos a:');
        $this->line("  - Asesor: {$advisorEmail}");
        $this->line("  - Cliente: {$clientEmail}");

        if (! $this->confirm('¿Desea enviar los correos de prueba?')) {
            $this->info('❌ Envío cancelado por el usuario');

            return 0;
        }

        try {
            $this->info('📤 Enviando correos...');

            // Enviar un solo correo al cliente con copia al asesor
            Mail::to($clientEmail)
                ->cc($advisorEmail)
                ->send(new DocumentsReadyMail($agreement));

            $this->info("✅ Correo enviado al cliente: {$clientEmail}");
            $this->info("✅ Copia enviada al asesor: {$advisorEmail}");

            $this->info('📊 Resumen del envío:');
            $this->line("  - Asesor: {$advisorEmail}");
            $this->line("  - Cliente: {$clientEmail}");
            $this->line("  - Documentos adjuntos: {$documentsWithFiles->count()}");
            $this->line("  - Convenio ID: {$agreement->id}");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar correos: '.$e->getMessage());

            return 1;
        }
    }
}
