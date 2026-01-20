<?php

namespace App\Notifications;

use App\Models\FinalPriceAuthorization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinalPriceAuthorizationApprovedNotification extends Notification implements ShouldQueue
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
            ->subject('✅ Precio Final Aprobado - Convenio #'.$authorization->agreement_id)
            ->greeting('¡Excelente noticia!')
            ->line('Tu solicitud de precio final ha sido **aprobada**.')
            ->line('**Convenio:** #'.$authorization->agreement_id)
            ->line('**Cliente:** '.$authorization->agreement->client->name)
            ->line('**Precio Aprobado:** $'.number_format($authorization->final_price, 2))
            ->line('**Aprobado por:** '.$authorization->reviewer->name)
            ->action('Ver Convenio', url('/admin/manage-documents/'.$authorization->agreement_id))
            ->line('El precio final ha sido registrado en el convenio.');
    }

    public function toArray(object $notifiable): array
    {
        $authorization = FinalPriceAuthorization::with(['agreement.client'])->find($this->authorizationId);

        return [
            'authorization_id' => $this->authorizationId,
            'agreement_id' => $authorization->agreement_id,
            'client_name' => $authorization->agreement->client->name,
            'final_price' => $authorization->final_price,
            'message' => 'Precio final aprobado para convenio #'.$authorization->agreement_id,
        ];
    }
}
