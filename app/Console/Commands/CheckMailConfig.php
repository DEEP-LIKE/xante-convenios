<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class CheckMailConfig extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'check:mail-config';

    /**
     * The console command description.
     */
    protected $description = 'Check mail configuration for Mailtrap or other mail services';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("📧 Verificando configuración de correo...");
        $this->newLine();

        // Configuración general
        $defaultMailer = Config::get('mail.default');
        $this->info("🔧 Mailer por defecto: {$defaultMailer}");

        // Configuración SMTP
        if ($defaultMailer === 'smtp') {
            $host = Config::get('mail.mailers.smtp.host');
            $port = Config::get('mail.mailers.smtp.port');
            $username = Config::get('mail.mailers.smtp.username');
            $password = Config::get('mail.mailers.smtp.password') ? '***configurado***' : 'NO CONFIGURADO';

            $this->info("🌐 Host SMTP: {$host}");
            $this->info("🔌 Puerto: {$port}");
            $this->info("👤 Usuario: {$username}");
            $this->info("🔑 Contraseña: {$password}");

            // Detectar si es Mailtrap
            if (str_contains($host, 'mailtrap') || str_contains($host, 'sandbox.smtp.mailtrap.io')) {
                $this->info("✅ Configuración detectada: Mailtrap");
                $this->warn("⚠️  Recuerda que Mailtrap es solo para pruebas - los correos no se entregan realmente");
            }
        }

        // Configuración From
        $fromAddress = Config::get('mail.from.address');
        $fromName = Config::get('mail.from.name');
        $this->info("📤 Dirección remitente: {$fromAddress}");
        $this->info("👤 Nombre remitente: {$fromName}");

        $this->newLine();

        // Verificar variables de entorno críticas
        $this->info("🔍 Variables de entorno:");
        $envVars = [
            'MAIL_MAILER',
            'MAIL_HOST', 
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME'
        ];

        foreach ($envVars as $var) {
            $value = env($var);
            if ($value) {
                if (in_array($var, ['MAIL_PASSWORD'])) {
                    $displayValue = '***configurado***';
                } else {
                    $displayValue = $value;
                }
                $this->line("  ✅ {$var}: {$displayValue}");
            } else {
                $this->line("  ❌ {$var}: NO CONFIGURADO");
            }
        }

        $this->newLine();
        
        // Sugerencias
        $this->info("💡 Sugerencias:");
        $this->line("  - Para Mailtrap: Usa sandbox.smtp.mailtrap.io:2525");
        $this->line("  - Para Gmail: Usa smtp.gmail.com:587 con contraseña de aplicación");
        $this->line("  - Para pruebas locales: Usa 'log' como MAIL_MAILER");
        
        $this->newLine();
        $this->info("🧪 Para probar el envío de correos:");
        $this->line("  php artisan test:email-sending {agreement_id} --email=tu@email.com");

        return 0;
    }
}
