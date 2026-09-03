<?php

namespace App\Actions\Agreements;

use App\Mail\DocumentsReadyMail;
use App\Models\Agreement;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDocumentsAction
{
    /**
     * Ejecuta el envío de documentos al cliente.
     *
     * @return int Número de documentos enviados
     *
     * @throws \Exception
     */
    public function execute(Agreement $agreement, User $advisor): int
    {
        @set_time_limit(300);

        // Validar que existen documentos generados
        if ($agreement->generatedDocuments->isEmpty()) {
            throw new \Exception('No hay documentos generados para enviar. Por favor, genere los documentos primero.');
        }

        // Validar email del cliente
        $clientEmail = $this->getClientEmail($agreement);
        \Log::debug('Client email obtained', ['email' => $clientEmail]);

        if ($clientEmail === 'No disponible' || empty($clientEmail)) {
            throw new \Exception('El cliente no tiene un email registrado en el convenio. Por favor, actualice los datos del cliente.');
        }

        // Validar que los archivos PDF existen físicamente
        $documentsWithFiles = $agreement->generatedDocuments->filter(function ($document) {
            $exists = $document->fileExists();
            \Log::debug('Checking file existence', ['path' => $document->file_path, 'exists' => $exists]);
            return $exists;
        });

        if ($documentsWithFiles->isEmpty()) {
            \Log::warning('No generated documents with physical files found', ['agreement_id' => $agreement->id]);
            throw new \Exception('Los archivos PDF no se encontraron en el servidor. Por favor, regenere los documentos.');
        }

        \Log::debug('Documents found for sending', ['count' => $documentsWithFiles->count()]);

        try {
            $ccRecipients = $this->getCcRecipients($advisor->email);

            // 1. Enviar el correo con los archivos adjuntos
            Mail::to($clientEmail)
                ->cc($ccRecipients)
                ->send(new DocumentsReadyMail($agreement));

            // 2. Actualizar estado del convenio SOLO tras envío exitoso
            $agreement->update([
                'status' => 'documents_sent',
                'documents_sent_at' => now(),
            ]);

            Log::info('Documents sent and status updated successfully', [
                'agreement_id' => $agreement->id,
                'documents_count' => $documentsWithFiles->count(),
                'cc_recipients' => $ccRecipients,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in SendDocumentsAction', [
                'agreement_id' => $agreement->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('No se pudo enviar el correo: ' . $e->getMessage());
        }

        return $documentsWithFiles->count();
    }

    private function getClientEmail(Agreement $agreement): string
    {
        $wizardData = $agreement->wizard_data ?? [];
        $holderEmail = $wizardData['holder_email'] ?? null;

        if (! $holderEmail && $agreement->client) {
            $holderEmail = $agreement->client->email;
        }

        return $holderEmail ?? 'No disponible';
    }

    private function getCcRecipients(?string $advisorEmail): array
    {
        $recipients = ['convenios@xante.mx'];

        if ($advisorEmail && ! str_ends_with(strtolower($advisorEmail), '@xante.com') && filter_var($advisorEmail, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = $advisorEmail;
        }

        return array_values(array_unique($recipients));
    }
}
