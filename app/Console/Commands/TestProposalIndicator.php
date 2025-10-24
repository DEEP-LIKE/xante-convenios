<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\Agreement;
use Illuminate\Support\Facades\Auth;

class TestProposalIndicator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:proposal-indicator {--create-data : Crear datos de prueba}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la funcionalidad del indicador de pre-cálculo previo en el Wizard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando funcionalidad del Indicador de Pre-cálculo Previo');
        $this->newLine();

        if ($this->option('create-data')) {
            $this->createTestData();
            return;
        }

        // Paso 1: Verificar estructura de la tabla proposals
        $this->info('📋 Paso 1: Verificando estructura de la tabla proposals...');
        $this->checkProposalsTable();

        // Paso 2: Buscar clientes con propuestas enlazadas
        $this->info('🔍 Paso 2: Buscando clientes con propuestas enlazadas...');
        $this->findClientsWithProposals();

        // Paso 3: Probar el método hasExistingProposal()
        $this->info('⚙️ Paso 3: Probando método de detección...');
        $this->testDetectionMethod();

        // Paso 4: Verificar URLs del wizard
        $this->info('🌐 Paso 4: Verificando URLs del wizard...');
        $this->checkWizardUrls();

        $this->newLine();
        $this->info('✅ Pruebas completadas. Revisa los resultados arriba.');
    }

    private function checkProposalsTable()
    {
        try {
            $proposalCount = Proposal::count();
            $linkedCount = Proposal::where('linked', true)->count();
            
            $this->line("  ✅ Tabla proposals existe");
            $this->line("  📊 Total propuestas: {$proposalCount}");
            $this->line("  🔗 Propuestas enlazadas: {$linkedCount}");
            
            if ($linkedCount === 0) {
                $this->warn("  ⚠️ No hay propuestas enlazadas para probar");
                $this->line("  💡 Ejecuta: php artisan test:proposal-indicator --create-data");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error al verificar tabla proposals: " . $e->getMessage());
        }
        $this->newLine();
    }

    private function findClientsWithProposals()
    {
        try {
            $proposals = Proposal::where('linked', true)
                ->with('client')
                ->latest()
                ->take(5)
                ->get();

            if ($proposals->isEmpty()) {
                $this->warn("  ⚠️ No se encontraron propuestas enlazadas");
                return;
            }

            $this->line("  📋 Propuestas enlazadas encontradas:");
            foreach ($proposals as $proposal) {
                $client = $proposal->client;
                $valorConvenio = $proposal->valor_convenio ?? 0;
                $ganancia = $proposal->ganancia_final ?? 0;
                
                $this->line("    • Cliente: {$client->name} (ID: {$proposal->idxante})");
                $this->line("      Valor: $" . number_format($valorConvenio, 2) . " | Ganancia: $" . number_format($ganancia, 2));
                $this->line("      Fecha: " . $proposal->created_at->format('d/m/Y H:i'));
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error al buscar propuestas: " . $e->getMessage());
        }
        $this->newLine();
    }

    private function testDetectionMethod()
    {
        try {
            // Buscar un cliente con propuesta
            $proposal = Proposal::where('linked', true)->with('client')->first();
            
            if (!$proposal) {
                $this->warn("  ⚠️ No hay propuestas para probar");
                return;
            }

            $client = $proposal->client;
            $this->line("  🧪 Probando con cliente: {$client->name} (ID: {$client->xante_id})");

            // Simular datos del wizard
            $testData = ['client_id' => $client->id];
            
            // Crear instancia temporal del wizard para probar
            $wizardClass = new \App\Filament\Pages\CreateAgreementWizard();
            $wizardClass->data = $testData;
            
            // Usar reflexión para acceder al método protegido
            $reflection = new \ReflectionClass($wizardClass);
            $method = $reflection->getMethod('hasExistingProposal');
            $method->setAccessible(true);
            
            $result = $method->invoke($wizardClass);
            
            if ($result) {
                $this->line("  ✅ Método hasExistingProposal() funciona correctamente");
                $this->line("    📅 Fecha: " . $result['created_at']->format('d/m/Y H:i'));
                $this->line("    💰 Valor: $" . number_format($result['valor_convenio'], 2));
                $this->line("    💵 Ganancia: $" . number_format($result['ganancia_final'], 2));
            } else {
                $this->error("  ❌ Método no detectó la propuesta existente");
            }
            
        } catch (\Exception $e) {
            $this->error("  ❌ Error al probar método: " . $e->getMessage());
        }
        $this->newLine();
    }

    private function checkWizardUrls()
    {
        try {
            $this->line("  🌐 URLs importantes del sistema:");
            $this->line("    • Calculadora Previa: /admin/quote-calculator");
            $this->line("    • Crear Convenio: /admin/convenios/crear");
            $this->line("    • Lista Convenios: /admin/wizard");
            
            // Verificar si hay agreements para probar
            $agreementCount = Agreement::count();
            $this->line("  📊 Convenios existentes: {$agreementCount}");
            
            if ($agreementCount > 0) {
                $agreement = Agreement::latest()->first();
                $this->line("    • Último convenio: /admin/convenios/crear?agreement={$agreement->id}");
            }
            
        } catch (\Exception $e) {
            $this->error("  ❌ Error al verificar URLs: " . $e->getMessage());
        }
        $this->newLine();
    }

    private function createTestData()
    {
        $this->info('🏗️ Creando datos de prueba...');
        
        try {
            // Crear cliente de prueba
            $client = Client::firstOrCreate([
                'xante_id' => 'TEST001'
            ], [
                'name' => 'Cliente Prueba Indicador',
                'email' => 'test.indicador@example.com',
                'phone' => '1234567890',
                'holder_name' => 'Cliente Prueba',
                'created_by' => 1
            ]);

            $this->line("  ✅ Cliente creado: {$client->name} (ID: {$client->xante_id})");

            // Crear propuesta enlazada
            $proposalData = [
                'valor_convenio' => 500000,
                'ganancia_final' => 75000,
                'porcentaje_ganancia' => 15,
                'enganche' => 50000,
                'financiamiento' => 450000,
                'plazo_meses' => 24,
                'mensualidad' => 18750
            ];

            $proposal = Proposal::updateOrCreate([
                'idxante' => $client->xante_id,
                'linked' => true
            ], [
                'client_id' => $client->id,
                'data' => $proposalData,
                'created_by' => 1
            ]);

            $this->line("  ✅ Propuesta creada con valor: $" . number_format($proposalData['valor_convenio'], 2));

            // Crear agreement de prueba
            $agreement = Agreement::create([
                'client_xante_id' => $client->xante_id,
                'status' => 'draft',
                'current_step' => 1,
                'wizard_data' => ['client_id' => $client->id],
                'created_by' => 1
            ]);

            $this->line("  ✅ Convenio de prueba creado (ID: {$agreement->id})");

            $this->newLine();
            $this->info('🎯 Datos de prueba creados exitosamente!');
            $this->info('📋 Para probar el indicador:');
            $this->line("  1. Ve a: /admin/convenios/crear?agreement={$agreement->id}");
            $this->line("  2. Navega hasta el Paso 4 (Calculadora)");
            $this->line("  3. Deberías ver el indicador amarillo con los datos precargados");
            
        } catch (\Exception $e) {
            $this->error('❌ Error al crear datos de prueba: ' . $e->getMessage());
        }
    }
}
