<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agreement;
use App\Models\Client;

class UpdateAgreementNames extends Command
{
    protected $signature = 'update:agreement-names';
    protected $description = 'Actualiza los nombres de los convenios desde wizard_data';

    public function handle()
    {
        $this->info('Actualizando nombres de convenios...');
        
        $agreements = Agreement::all();
        $updated = 0;
        
        foreach ($agreements as $agreement) {
            $wizardData = $agreement->wizard_data ? $agreement->wizard_data : [];
            $needsUpdate = false;
            $updateData = [];
            
            // Actualizar holder_name si está en wizard_data pero no en el campo directo
            if (!empty($wizardData['holder_name']) && empty($agreement->holder_name)) {
                $updateData['holder_name'] = $wizardData['holder_name'];
                $needsUpdate = true;
            }
            
            // Actualizar holder_email
            if (!empty($wizardData['holder_email']) && empty($agreement->holder_email)) {
                $updateData['holder_email'] = $wizardData['holder_email'];
                $needsUpdate = true;
            }
            
            // Actualizar holder_phone
            if (!empty($wizardData['holder_phone']) && empty($agreement->holder_phone)) {
                $updateData['holder_phone'] = $wizardData['holder_phone'];
                $needsUpdate = true;
            }
            
            // Actualizar client_xante_id
            if (!empty($wizardData['xante_id']) && empty($agreement->client_xante_id)) {
                $updateData['client_xante_id'] = $wizardData['xante_id'];
                $needsUpdate = true;
            }
            
            if ($needsUpdate) {
                $agreement->update($updateData);
                $updated++;
                
                $holderName = isset($updateData['holder_name']) ? $updateData['holder_name'] : 'Sin nombre';
                $clientId = isset($updateData['client_xante_id']) ? $updateData['client_xante_id'] : 'Sin ID';
                $this->line("✓ Convenio {$agreement->id}: {$holderName} ({$clientId})");
            }
        }
        
        $this->info("Proceso completado. {$updated} convenios actualizados de {$agreements->count()} total.");
        
        // Mostrar estadísticas
        $this->newLine();
        $this->info('=== ESTADÍSTICAS ===');
        
        $withNames = Agreement::whereNotNull('holder_name')->where('holder_name', '!=', '')->count();
        $withXanteId = Agreement::whereNotNull('client_xante_id')->where('client_xante_id', '!=', '')->count();
        $withWizardData = Agreement::whereNotNull('wizard_data')->count();
        
        $this->line("Convenios con holder_name: {$withNames}");
        $this->line("Convenios con client_xante_id: {$withXanteId}");
        $this->line("Convenios con wizard_data: {$withWizardData}");
        
        return Command::SUCCESS;
    }
}
