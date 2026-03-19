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

        $roleLabel = match ($notifiable->role) {
            'gerencia' => 'Administrador',
            'coordinador_fi' => 'Coordinador FI',
            'ejecutivo' => 'Asesor',
            default => 'Usuario',
        };

        return (new MailMessage)
            ->subject('✓ Validación Aprobada - Convenio #'.$validation->agreement_id)
            ->greeting('¡Excelente noticia, '.$roleLabel.'!')
            ->line('Tu calculadora ha sido **aprobada** por el Coordinador FI.')
            ->line('**Convenio ID:** #'.$validation->agreement_id)
            ->line('**Aprobado por:** '.$validation->validatedBy->name)
            ->line('**Fecha:** '.$validation->validated_at->format('d/m/Y H:i'))
            ->action('Continuar con el Convenio', url('/admin/convenios/crear?agreement='.$validation->agreement_id.'&step=form.data%3A%3Awizard.validacion%3A%3Adata%3A%3Awizard-step'))
            ->line('Ya puedes continuar con la generación de documentos.')
            ->salutation('Saludos, Xante');
    }

    public function toDatabase(object $notifiable): array
    {
        $validation = QuoteValidation::find($this->validationId);

        if (! $validation) {
            return \Filament\Notifications\Notification::make()
                ->title('Validación no disponible')
                ->body('La validación solicitada ya no está disponible.')
                ->warning()
                ->getDatabaseMessage();
        }

        return \Filament\Notifications\Notification::make()
            ->title('Validación Aprobada')
            ->body("El convenio #{$validation->agreement_id} ha sido aprobado por {$validation->validatedBy->name}.")
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Ver Convenio')
                    ->url(url('/admin/convenios/crear?agreement='.$validation->agreement_id.'&step=form.data%3A%3Awizard.validacion%3A%3Adata%3A%3Awizard-step'), shouldOpenInNewTab: true),
            ])
            ->getDatabaseMessage();
    }

    public function toArray(object $notifiable): array
    {
        $validation = QuoteValidation::find($this->validationId);

        if (! $validation) {
            return [
                'validation_id' => $this->validationId,
                'type' => 'validation_approved',
                'status' => 'deleted',
            ];
        }

        return [
            'validation_id' => $validation->id,
            'agreement_id' => $validation->agreement_id,
            'validated_by' => $validation->validatedBy->name,
            'type' => 'validation_approved',
        ];
    }
}
