<?php

namespace App\Notifications;

use App\Models\FinalPriceAuthorization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinalPriceAuthorizationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $authorizationId
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $authorization = FinalPriceAuthorization::with(['agreement.client', 'reviewer'])->find($this->authorizationId);

        return (new MailMessage)
            ->subject('❌ Precio Final Rechazado - Convenio #'.$authorization->agreement_id)
            ->greeting('Hola,')
            ->line('Tu solicitud de precio final ha sido **rechazada**.')
            ->line('**Convenio:** #'.$authorization->agreement_id)
            ->line('**Cliente:** '.$authorization->agreement->client->name)
            ->line('**Precio Solicitado:** $'.number_format($authorization->final_price, 2))
            ->line('**Rechazado por:** '.$authorization->reviewer->name)
            ->line('**Motivo:** '.$authorization->rejection_reason)
            ->action('Ver Convenio', url('/admin/manage-documents/'.$authorization->agreement_id))
            ->line('Puedes solicitar una nueva autorización con un precio diferente o una justificación más detallada.');
    }

    public function toArray(object $notifiable): array
    {
        $authorization = FinalPriceAuthorization::with(['agreement.client'])->find($this->authorizationId);

        return [
            'authorization_id' => $this->authorizationId,
            'agreement_id' => $authorization->agreement_id,
            'client_name' => $authorization->agreement->client->name,
            'final_price' => $authorization->final_price,
            'rejection_reason' => $authorization->rejection_reason,
            'message' => 'Precio final rechazado para convenio #'.$authorization->agreement_id,
        ];
    }
}
