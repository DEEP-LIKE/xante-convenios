<?php

namespace App\Console\Commands;

use App\Models\Agreement;
use App\Models\Client;
use Illuminate\Console\Command;

class CheckClientAgreementRelations extends Command
{
    protected $signature = 'check:client-agreements';

    protected $description = 'Verifica el estado de las relaciones entre clientes y convenios';

    public function handle()
    {
        $this->info('Verificando relaciones Cliente-Convenio...');
        $this->newLine();

        // Estadísticas generales
        $totalClients = Client::count();
        $totalAgreements = Agreement::count();

        $this->info('📊 ESTADÍSTICAS GENERALES:');
        $this->line("   Total de clientes: {$totalClients}");
        $this->line("   Total de convenios: {$totalAgreements}");
        $this->newLine();

        // Convenios sin relación de cliente
        $agreementsWithoutClient = Agreement::whereNull('client_xante_id')->count();
        $agreementsWithInvalidClient = Agreement::whereNotNull('client_xante_id')
            ->whereDoesntHave('client')
            ->count();

        $this->info('🔗 RELACIONES:');
        $this->line("   Convenios sin client_xante_id: {$agreementsWithoutClient}");
        $this->line("   Convenios con client_xante_id inválido: {$agreementsWithInvalidClient}");

        $validRelations = $totalAgreements - $agreementsWithoutClient - $agreementsWithInvalidClient;
        $this->line("   Relaciones válidas: {$validRelations}");
        $this->newLine();

        // Clientes con y sin convenios
        $clientsWithAgreements = Client::has('agreements')->count();
        $clientsWithoutAgreements = $totalClients - $clientsWithAgreements;

        $this->info('👥 CLIENTES:');
        $this->line("   Clientes con convenios: {$clientsWithAgreements}");
        $this->line("   Clientes sin convenios: {$clientsWithoutAgreements}");
        $this->newLine();

        // Estados de convenios
        $this->info('📋 ESTADOS DE CONVENIOS:');
        $statusCounts = Agreement::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        foreach ($statusCounts as $status) {
            $this->line("   {$status->status}: {$status->count}");
        }
        $this->newLine();

        // Mostrar algunos ejemplos de problemas
        if ($agreementsWithoutClient > 0) {
            $this->warn('⚠️  CONVENIOS SIN CLIENTE:');
            $examples = Agreement::whereNull('client_xante_id')
                ->select('id', 'holder_name', 'status', 'created_at')
                ->limit(5)
                ->get();

            foreach ($examples as $agreement) {
                $this->line("   ID: {$agreement->id} | Titular: {$agreement->holder_name} | Estado: {$agreement->status}");
            }

            if ($agreementsWithoutClient > 5) {
                $remaining = $agreementsWithoutClient - 5;
                $this->line("   ... y {$remaining} más");
            }
            $this->newLine();
        }

        if ($agreementsWithInvalidClient > 0) {
            $this->warn('⚠️  CONVENIOS CON CLIENTE INVÁLIDO:');
            $examples = Agreement::whereNotNull('client_xante_id')
                ->whereDoesntHave('client')
                ->select('id', 'client_xante_id', 'holder_name', 'status')
                ->limit(5)
                ->get();

            foreach ($examples as $agreement) {
                $this->line("   ID: {$agreement->id} | Client ID: {$agreement->client_xante_id} | Titular: {$agreement->holder_name}");
            }

            if ($agreementsWithInvalidClient > 5) {
                $remaining = $agreementsWithInvalidClient - 5;
                $this->line("   ... y {$remaining} más");
            }
            $this->newLine();
        }

        // Recomendaciones
        if ($agreementsWithoutClient > 0 || $agreementsWithInvalidClient > 0) {
            $this->warn('💡 RECOMENDACIÓN:');
            $this->line("   Ejecuta 'php artisan sync:client-agreements' para corregir las relaciones.");
        } else {
            $this->info('✅ Todas las relaciones están correctas!');
        }

        return Command::SUCCESS;
    }
}
