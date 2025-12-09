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
        public int $validationId
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $validation = QuoteValidation::find($this->validationId);
        
        if (!$validation) {
            return (new MailMessage)
                ->subject('Validación no disponible')
                ->line('La validación solicitada ya no está disponible.');
        }

        return (new MailMessage)
            ->subject('Observaciones en tu Validación - Convenio #' . $validation->agreement_id)
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('El Coordinador FI ha solicitado cambios en tu calculadora.')
            ->line('**Convenio ID:** #' . $validation->agreement_id)
            ->line('**Revisado por:** ' . $validation->validatedBy->name)
            ->line('**Observaciones:**')
            ->line($validation->observations)
            ->action('Ver Observaciones', url('/wizard/' . $validation->agreement_id))
            ->line('Por favor realiza los ajustes necesarios y reenvía a validación.');
    }

    public function toArray(object $notifiable): array
    {
        $validation = QuoteValidation::find($this->validationId);
        
        if (!$validation) {
            return [
                'validation_id' => $this->validationId,
                'type' => 'validation_with_observations',
                'status' => 'deleted',
            ];
        }

        return [
            'validation_id' => $validation->id,
            'agreement_id' => $validation->agreement_id,
            'validated_by' => $validation->validatedBy->name,
            'observations' => $validation->observations,
            'type' => 'validation_with_observations',
        ];
    }
}
