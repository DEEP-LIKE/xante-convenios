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
            return (new MailMessage)
                ->subject('Validación no disponible')
                ->line('La validación solicitada ya no está disponible.');
        }

        return (new MailMessage)
            ->subject('Validación Rechazada - Convenio #'.$validation->agreement_id)
            ->greeting('Hola '.$notifiable->name.',')
            ->line('Tu calculadora ha sido **rechazada** por el Coordinador FI.')
            ->line('**Convenio ID:** #'.$validation->agreement_id)
            ->line('**Rechazado por:** '.$validation->validatedBy->name)
            ->line('**Motivo:**')
            ->line($validation->observations)
            ->action('Ver Detalles', url('/wizard/'.$validation->agreement_id))
            ->line('Por favor revisa el motivo y realiza las correcciones necesarias.');
    }

    public function toArray(object $notifiable): array
    {
        $validation = QuoteValidation::find($this->validationId);

        if (! $validation) {
            return [
                'validation_id' => $this->validationId,
                'type' => 'validation_rejected',
                'status' => 'deleted',
            ];
        }

        return [
            'validation_id' => $validation->id,
            'agreement_id' => $validation->agreement_id,
            'validated_by' => $validation->validatedBy->name,
            'reason' => $validation->observations,
            'type' => 'validation_rejected',
        ];
    }
}
