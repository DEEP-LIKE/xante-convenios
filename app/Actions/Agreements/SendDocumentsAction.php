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
        // Validar que existen documentos generados
        if ($agreement->generatedDocuments->isEmpty()) {
            throw new \Exception('No hay documentos generados para enviar. Por favor, genere los documentos primero.');
        }

        // Validar email del cliente
        $clientEmail = $this->getClientEmail($agreement);
        if ($clientEmail === 'No disponible' || empty($clientEmail)) {
            throw new \Exception('El cliente no tiene un email registrado en el convenio. Por favor, actualice los datos del cliente.');
        }

        // Validar que los archivos PDF existen físicamente
        $documentsWithFiles = $agreement->generatedDocuments->filter(function ($document) {
            return $document->fileExists();
        });

        if ($documentsWithFiles->isEmpty()) {
            throw new \Exception('Los archivos PDF no se encontraron en el servidor. Por favor, regenere los documentos.');
        }

        // Actualizar estado del convenio
        $agreement->update([
            'status' => 'documents_sent',
            'documents_sent_at' => now(),
        ]);

        // Enviar el correo al cliente con copia al asesor
        try {
            Mail::to($clientEmail)
                ->cc($advisor->email)
                ->send(new DocumentsReadyMail($agreement));
        } catch (\Exception $e) {
            Log::error('Error sending email via Mail facade', ['error' => $e->getMessage()]);
            throw new \Exception('Error al enviar el correo electrónico: '.$e->getMessage());
        }

        Log::info('Documents sent successfully via Action', [
            'agreement_id' => $agreement->id,
            'client_email' => $clientEmail,
            'advisor_email' => $advisor->email,
            'documents_count' => $documentsWithFiles->count(),
        ]);

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
}
