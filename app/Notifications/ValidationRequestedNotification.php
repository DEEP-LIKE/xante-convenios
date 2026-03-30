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
            // Si la validación ya no existe, no enviar el correo
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

        $isUnder3Years = false;
        $fechaPropiedad = $validation->agreement->wizard_data['fecha_propiedad'] ?? null;
        if ($fechaPropiedad) {
            $isUnder3Years = \Carbon\Carbon::parse($fechaPropiedad)->diffInYears(now()) < 3;
        }

        $mail = (new MailMessage)
            ->subject('Nueva Validación Pendiente - Convenio #'.$validation->agreement_id)
            ->greeting('¡Hola '.$roleLabel.'!')
            ->line('Hay una nueva calculadora pendiente de validación.');
            
        if ($isUnder3Years) {
            $mail->line(new \Illuminate\Support\HtmlString('
                <div style="background-color: #fff3cd; color: #856404; padding: 15px; border-left: 5px solid #ffeeba; border-radius: 4px; margin-top: 15px; margin-bottom: 15px;">
                    <strong>ALERTA:</strong> La propiedad tiene menos de 3 años de escrituración. Por favor tener especial atención en esta parte.
                </div>
            '));
        }

        return $mail
            ->line('**Ejecutivo:** '.$validation->requestedBy->name)
            ->line('**Convenio ID:** #'.$validation->agreement_id)
            ->line('**Revisión:** #'.$validation->revision_number)
            ->action('Revisar Validación', url('/admin/quote-validations/'.$validation->id.'/edit'))
            ->line('Por favor revisa los cálculos y aprueba o solicita cambios.')
            ->salutation('Saludos, Xante');
    }

    public function toArray(object $notifiable): array
    {
        $validation = QuoteValidation::find($this->validationId);

        if (! $validation) {
            return [
                'validation_id' => $this->validationId,
                'type' => 'validation_requested',
                'status' => 'deleted',
            ];
        }

        return [
            'validation_id' => $validation->id,
            'agreement_id' => $validation->agreement_id,
            'requested_by' => $validation->requestedBy->name,
            'revision_number' => $validation->revision_number,
            'type' => 'validation_requested',
        ];
    }
}
