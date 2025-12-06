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
        public QuoteValidation $validation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Validación Pendiente - Convenio #' . $this->validation->agreement_id)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Hay una nueva calculadora pendiente de validación.')
            ->line('**Ejecutivo:** ' . $this->validation->requestedBy->name)
            ->line('**Convenio ID:** #' . $this->validation->agreement_id)
            ->line('**Revisión:** #' . $this->validation->revision_number)
            ->action('Revisar Validación', url('/admin/quote-validations/' . $this->validation->id . '/view'))
            ->line('Por favor revisa los cálculos y aprueba o solicita cambios.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'validation_id' => $this->validation->id,
            'agreement_id' => $this->validation->agreement_id,
            'requested_by' => $this->validation->requestedBy->name,
            'revision_number' => $this->validation->revision_number,
            'type' => 'validation_requested',
        ];
    }
}
