<?php

namespace App\Notifications;

use App\Models\QuoteValidation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ValidationWithObservationsNotification extends Notification implements ShouldQueue
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
            ->subject('Observaciones en tu Validación - Convenio #' . $this->validation->agreement_id)
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('El Coordinador FI ha solicitado cambios en tu calculadora.')
            ->line('**Convenio ID:** #' . $this->validation->agreement_id)
            ->line('**Revisado por:** ' . $this->validation->validatedBy->name)
            ->line('**Observaciones:**')
            ->line($this->validation->observations)
            ->action('Ver Observaciones', url('/wizard/' . $this->validation->agreement_id))
            ->line('Por favor realiza los ajustes necesarios y reenvía a validación.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'validation_id' => $this->validation->id,
            'agreement_id' => $this->validation->agreement_id,
            'validated_by' => $this->validation->validatedBy->name,
            'observations' => $this->validation->observations,
            'type' => 'validation_with_observations',
        ];
    }
}
