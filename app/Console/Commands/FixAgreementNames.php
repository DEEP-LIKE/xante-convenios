<?php

namespace App\Console\Commands;

use App\Models\Agreement;
use Illuminate\Console\Command;

class FixAgreementNames extends Command
{
    protected $signature = 'fix:agreement-names';

    protected $description = 'Corrige los nombres de los convenios';

    public function handle()
    {
        $this->info('Corrigiendo nombres de convenios...');

        $agreements = Agreement::all();
        $updated = 0;

        foreach ($agreements as $agreement) {
            $wizardData = $agreement->wizard_data;
            $needsUpdate = false;

            if ($wizardData && is_array($wizardData)) {
                if (isset($wizardData['holder_name']) && empty($agreement->holder_name)) {
                    $agreement->holder_name = $wizardData['holder_name'];
                    $needsUpdate = true;
                }

                if (isset($wizardData['xante_id']) && empty($agreement->client_xante_id)) {
                    $agreement->client_xante_id = $wizardData['xante_id'];
                    $needsUpdate = true;
                }

                if (isset($wizardData['holder_email']) && empty($agreement->holder_email)) {
                    $agreement->holder_email = $wizardData['holder_email'];
                    $needsUpdate = true;
                }
            }

            if ($needsUpdate) {
                $agreement->save();
                $updated++;
                $this->line("✓ Convenio {$agreement->id}: {$agreement->holder_name}");
            }
        }

        $this->info("Actualizados {$updated} convenios de {$agreements->count()} total.");

        return Command::SUCCESS;
    }
}
