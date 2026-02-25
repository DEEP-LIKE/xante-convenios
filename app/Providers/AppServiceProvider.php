<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzar idioma español en toda la aplicación
        App::setLocale('es');

        // Enviar copia oculta de todos los correos para monitoreo
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Mail\Events\MessageSending::class,
            function (\Illuminate\Mail\Events\MessageSending $event) {
                $event->message->addBcc('juancarlos.ruiz@carbono.mx');
            }
        );
    }
}
