<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncHubspotClientsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programación de Sincronización HubSpot (3 veces al día)
Schedule::job(new SyncHubspotClientsJob())->dailyAt('09:00')->timezone('America/Mexico_City');
Schedule::job(new SyncHubspotClientsJob())->dailyAt('13:00')->timezone('America/Mexico_City');
Schedule::job(new SyncHubspotClientsJob())->dailyAt('16:00')->timezone('America/Mexico_City');

