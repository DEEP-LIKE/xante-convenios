<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Mail Configuration Diagnostic ---\n";
echo "MAIL_MAILER: " . Config::get('mail.default') . "\n";
echo "MAIL_HOST: " . Config::get('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . Config::get('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . Config::get('mail.mailers.smtp.username') . "\n";
echo "MAIL_FROM_ADDRESS: " . Config::get('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . Config::get('mail.from.name') . "\n";
echo "-------------------------------------\n";

try {
    echo "Attempting to send a test email to " . Config::get('mail.from.address') . "...\n";
    Mail::raw('Test email from Xante Diagnostic Script', function ($message) {
        $message->to(Config::get('mail.from.address'))
                ->subject('Xante Mail Diagnostic');
    });
    echo "SUCCESS: Test email sent via Mail facade.\n";
} catch (\Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
