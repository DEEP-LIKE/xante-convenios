<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\ClientDocument;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

/**
 * Servicio para gestionar envío de correos relacionados con documentos
 *
 * Responsabilidades:
 * - Enviar confirmación de documentos recibidos
 * - Validar emails de clientes
 * - Obtener información de contacto
 */
class DocumentEmailService
{
    /**
     * Obtiene el email del cliente desde wizard_data o modelo Client
     */
    public function getClientEmail(Agreement $agreement): string
    {
        $wizardData = $agreement->wizard_data ?? [];
        $holderEmail = $wizardData['holder_email'] ?? null;

        if (! $holderEmail && $agreement->client) {
            $holderEmail = $agreement->client->email;
        }

        return $holderEmail ?? 'No disponible';
    }

    /**
     * Obtiene el nombre del cliente
     */
    public function getClientName(Agreement $agreement): string
    {
        $wizardData = $agreement->wizard_data ?? [];
        $holderName = $wizardData['holder_name'] ?? null;

        if (! $holderName && $agreement->client) {
            $holderName = $agreement->client->name;
        }

        return $holderName ?? 'N/A';
    }

    /**
     * Valida que el cliente tenga un email válido
     */
    public function validateClientEmail(Agreement $agreement): bool
    {
        $clientEmail = $this->getClientEmail($agreement);

        return $clientEmail !== 'No disponible' && ! empty($clientEmail);
    }

    /**
     * Envía correo de confirmación de documentos recibidos
     */
    public function sendDocumentsReceivedConfirmation(Agreement $agreement): void
    {
        try {
            \Log::info('sendDocumentsReceivedConfirmation called', [
                'agreement_id' => $agreement->id,
                'user_id' => auth()->id(),
            ]);

            // Validar email del cliente
            if (! $this->validateClientEmail($agreement)) {
                Notification::make()
                    ->title('❌ Email No Disponible')
                    ->body('El cliente no tiene un email registrado en el convenio.')
                    ->warning()
                    ->duration(5000)
                    ->send();

                return;
            }

            $clientEmail = $this->getClientEmail($agreement);
            $ccRecipients = $this->getCcRecipients(auth()->user()?->email);
            $advisorName = auth()->user()->name ?? 'Asesor';

            // Obtener documentos del cliente
            $clientDocuments = ClientDocument::where('agreement_id', $agreement->id)->get();

            // Notificación de inicio
            Notification::make()
                ->title('📤 Enviando Confirmación...')
                ->body("Enviando confirmación de documentos recibidos a {$clientEmail} y al asesor {$advisorName}")
                ->info()
                ->duration(3000)
                ->send();

            \Log::info('Sending confirmation email', [
                'agreement_id' => $agreement->id,
                'client_email' => $clientEmail,
                'cc_recipients' => $ccRecipients,
                'documents_count' => $clientDocuments->count(),
            ]);

            // Enviar correo
            Mail::to($clientEmail)
                ->cc($ccRecipients)
                ->send(new \App\Mail\DocumentsReceivedConfirmationMail($agreement, $clientDocuments));

            \Log::info('Confirmation email sent successfully', [
                'agreement_id' => $agreement->id,
                'client_email' => $clientEmail,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error sending documents received confirmation', [
                'agreement_id' => $agreement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('❌ Error al Enviar Confirmación')
                ->body('Ocurrió un error al enviar la confirmación. Por favor, inténtelo nuevamente.')
                ->danger()
                ->duration(7000)
                ->send();

            throw $e;
        }
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
