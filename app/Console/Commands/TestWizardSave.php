<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agreement;
use App\Models\Client;

class TestWizardSave extends Command
{
    protected $signature = 'test:wizard-save {agreement_id}';
    protected $description = 'Prueba el guardado de datos del wizard';

    public function handle()
    {
        $agreementId = $this->argument('agreement_id');
        
        $agreement = Agreement::find($agreementId);
        
        if (!$agreement) {
            $this->error("Agreement ID {$agreementId} no encontrado");
            return;
        }

        $this->info("=== DATOS DEL CONVENIO ===");
        $this->info("ID: {$agreement->id}");
        $this->info("Cliente Xante ID: {$agreement->client_xante_id}");
        $this->info("Holder Name: {$agreement->holder_name}");
        $this->info("Current Step: {$agreement->current_step}");
        $this->info("Status: {$agreement->status}");
        
        $this->info("\n=== WIZARD DATA ===");
        if ($agreement->wizard_data) {
            foreach ($agreement->wizard_data as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $this->line("{$key}: {$value}");
                }
            }
        } else {
            $this->warn("No hay wizard_data");
        }
        
        $this->info("\n=== RELACIÓN CON CLIENTE ===");
        $client = $agreement->client;
        if ($client) {
            $this->info("Cliente encontrado: {$client->name} ({$client->xante_id})");
        } else {
            $this->warn("No se encontró cliente relacionado");
            
            // Buscar cliente manualmente
            if ($agreement->client_xante_id) {
                $manualClient = Client::where('xante_id', $agreement->client_xante_id)->first();
                if ($manualClient) {
                    $this->info("Cliente existe en BD: {$manualClient->name}");
                } else {
                    $this->error("Cliente no existe en BD con xante_id: {$agreement->client_xante_id}");
                }
            }
        }

        return Command::SUCCESS;
    }
}
