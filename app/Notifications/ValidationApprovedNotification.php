<?php

namespace App\Notifications;

use App\Models\QuoteValidation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ValidationApprovedNotification extends Notification implements ShouldQueue
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
            ->subject('✓ Validación Aprobada - Convenio #' . $this->validation->agreement_id)
            ->greeting('¡Excelente noticia, ' . $notifiable->name . '!')
            ->line('Tu calculadora ha sido **aprobada** por el Coordinador FI.')
            ->line('**Convenio ID:** #' . $this->validation->agreement_id)
            ->line('**Aprobado por:** ' . $this->validation->validatedBy->name)
            ->line('**Fecha:** ' . $this->validation->validated_at->format('d/m/Y H:i'))
            ->action('Continuar con el Convenio', url('/wizard/' . $this->validation->agreement_id))
            ->line('Ya puedes continuar con la generación de documentos.');
    }

    public function toDatabase(object $notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title('Validación Aprobada')
            ->body("El convenio #{$this->validation->agreement_id} ha sido aprobado por {$this->validation->validatedBy->name}.")
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Ver Convenio')
                    ->url(url('/admin/convenios/crear?agreement=' . $this->validation->agreement_id), shouldOpenInNewTab: true),
            ])
            ->getDatabaseMessage();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'validation_id' => $this->validation->id,
            'agreement_id' => $this->validation->agreement_id,
            'validated_by' => $this->validation->validatedBy->name,
            'type' => 'validation_approved',
        ];
    }
}
