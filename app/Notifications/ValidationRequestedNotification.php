<?php

namespace App\Notifications;

use App\Models\QuoteValidation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ValidationRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $validationId
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $validation = QuoteValidation::find($this->validationId);

        if (! $validation) {
            // Si la validación ya no existe, no enviar el correo
            return (new MailMessage)
                ->subject('Validación no disponible')
                ->line('La validación solicitada ya no está disponible.');
        }

        return (new MailMessage)
            ->subject('Nueva Validación Pendiente - Convenio #'.$validation->agreement_id)
            ->greeting('¡Hola '.$notifiable->name.'!')
            ->line('Hay una nueva calculadora pendiente de validación.')
            ->line('**Ejecutivo:** '.$validation->requestedBy->name)
            ->line('**Convenio ID:** #'.$validation->agreement_id)
            ->line('**Revisión:** #'.$validation->revision_number)
            ->action('Revisar Validación', url('/admin/quote-validations/'.$validation->id.'/view'))
            ->line('Por favor revisa los cálculos y aprueba o solicita cambios.');
    }

    public function toArray(object $notifiable): array
    {
        $validation = QuoteValidation::find($this->validationId);

        if (! $validation) {
            return [
                'validation_id' => $this->validationId,
                'type' => 'validation_requested',
                'status' => 'deleted',
            ];
        }

        return [
            'validation_id' => $validation->id,
            'agreement_id' => $validation->agreement_id,
            'requested_by' => $validation->requestedBy->name,
            'revision_number' => $validation->revision_number,
            'type' => 'validation_requested',
        ];
    }
}
