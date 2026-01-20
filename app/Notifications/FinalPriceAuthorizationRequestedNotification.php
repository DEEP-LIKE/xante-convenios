<?php

namespace App\Notifications;

use App\Models\FinalPriceAuthorization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinalPriceAuthorizationRequestedNotification extends Notification implements ShouldQueue
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
        $authorization = FinalPriceAuthorization::with(['agreement.client', 'requester'])->find($this->authorizationId);

        return (new MailMessage)
            ->subject('Nueva Solicitud de Precio Final - Convenio #'.$authorization->agreement_id)
            ->greeting('¡Hola!')
            ->line('Se ha recibido una nueva solicitud de autorización de precio final.')
            ->line('**Convenio:** #'.$authorization->agreement_id)
            ->line('**Cliente:** '.$authorization->agreement->client->name)
            ->line('**Precio Solicitado:** $'.number_format($authorization->final_price, 2))
            ->line('**Solicitado por:** '.$authorization->requester->name)
            ->action('Revisar Solicitud', url('/admin/final-price-authorizations/'.$authorization->id.'/edit'))
            ->line('Por favor, revisa y aprueba o rechaza esta solicitud.');
    }

    public function toArray(object $notifiable): array
    {
        $authorization = FinalPriceAuthorization::with(['agreement.client'])->find($this->authorizationId);

        return [
            'authorization_id' => $this->authorizationId,
            'agreement_id' => $authorization->agreement_id,
            'client_name' => $authorization->agreement->client->name,
            'final_price' => $authorization->final_price,
            'message' => 'Nueva solicitud de precio final para convenio #'.$authorization->agreement_id,
        ];
    }
}
