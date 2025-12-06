<?php

namespace App\Notifications;

use App\Models\QuoteValidation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ValidationRejectedNotification extends Notification implements ShouldQueue
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
            ->subject('Validación Rechazada - Convenio #' . $this->validation->agreement_id)
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Tu calculadora ha sido **rechazada** por el Coordinador FI.')
            ->line('**Convenio ID:** #' . $this->validation->agreement_id)
            ->line('**Rechazado por:** ' . $this->validation->validatedBy->name)
            ->line('**Motivo:**')
            ->line($this->validation->observations)
            ->action('Ver Detalles', url('/wizard/' . $this->validation->agreement_id))
            ->line('Por favor revisa el motivo y realiza las correcciones necesarias.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'validation_id' => $this->validation->id,
            'agreement_id' => $this->validation->agreement_id,
            'validated_by' => $this->validation->validatedBy->name,
            'reason' => $this->validation->observations,
            'type' => 'validation_rejected',
        ];
    }
}
