<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Proposal;
use App\Services\AgreementCalculatorService;

class FixProposalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:proposal-data {--test-value=1489500 : Valor de convenio para prueba}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige los datos de propuestas para guardar valores numéricos en lugar de formateados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Corrigiendo datos de propuestas...');
        $this->newLine();

        $testValue = (float) $this->option('test-value');
        
        // Crear propuesta de prueba con el valor especificado
        $this->createTestProposal($testValue);
        
        // Mostrar comparación de valores
        $this->showValueComparison($testValue);
        
        // Crear convenio de prueba
        $this->createTestAgreement();
        
        $this->newLine();
        $this->info('✅ Corrección completada. Ahora puedes probar el indicador en el wizard.');
    }

    private function createTestProposal(float $valorConvenio)
    {
        $this->info("📊 Creando propuesta de prueba con valor: $" . number_format($valorConvenio, 2));
        
        // Usar el servicio de cálculo para obtener valores correctos
        $calculatorService = app(AgreementCalculatorService::class);
        $calculations = $calculatorService->calculateAllFinancials($valorConvenio);
        
        // Buscar o crear cliente de prueba
        $client = \App\Models\Client::firstOrCreate([
            'xante_id' => 'TESTFIX001'
        ], [
            'name' => 'Cliente Prueba Valores Corregidos',
            'email' => 'test.fix@example.com',
            'phone' => '1234567890',
            'holder_name' => 'Cliente Prueba Fix',
            'created_by' => 1
        ]);

        // Crear propuesta con valores numéricos correctos
        $proposal = Proposal::updateOrCreate([
            'idxante' => $client->xante_id,
            'linked' => true
        ], [
            'client_id' => $client->id,
            'data' => $calculations, // Usar valores numéricos directamente
            'created_by' => 1
        ]);

        $this->line("  ✅ Cliente: {$client->name} (ID: {$client->xante_id})");
        $this->line("  ✅ Propuesta actualizada con valores numéricos correctos");
        
        // Mostrar los valores guardados
        $this->line("  📊 Valores guardados:");
        $this->line("    • Valor Convenio: $" . number_format($calculations['valor_convenio'], 2));
        $this->line("    • Ganancia Final: $" . number_format($calculations['ganancia_final'], 2));
        $this->line("    • Comisión Total: $" . number_format($calculations['comision_total_pagar'], 2));
    }

    private function showValueComparison(float $valorConvenio)
    {
        $this->info("🔍 Comparación de valores (Formateado vs Numérico):");
        
        $calculatorService = app(AgreementCalculatorService::class);
        $calculations = $calculatorService->calculateAllFinancials($valorConvenio);
        $formattedValues = $calculatorService->formatCalculationsForUI($calculations);
        
        $this->newLine();
        $this->line("📋 VALORES NUMÉRICOS (Correctos para BD):");
        $this->line("  • Ganancia Final: " . $calculations['ganancia_final']);
        $this->line("  • Comisión Total: " . $calculations['comision_total_pagar']);
        $this->line("  • Valor CompraVenta: " . $calculations['valor_compraventa']);
        
        $this->newLine();
        $this->line("🎨 VALORES FORMATEADOS (Solo para UI):");
        $this->line("  • Ganancia Final: " . $formattedValues['ganancia_final']);
        $this->line("  • Comisión Total: " . $formattedValues['comision_total_pagar']);
        $this->line("  • Valor CompraVenta: " . $formattedValues['valor_compraventa']);
        
        $this->newLine();
        $this->warn("⚠️ PROBLEMA ANTERIOR:");
        $this->line("  Se guardaban valores formateados (ej: '557,191.70')");
        $this->line("  Al convertir a float: (float)'557,191.70' = 557.00");
        
        $this->newLine();
        $this->info("✅ SOLUCIÓN IMPLEMENTADA:");
        $this->line("  Ahora se guardan valores numéricos directos");
        $this->line("  Al convertir a float: (float)557191.70 = 557191.70");
    }

    private function createTestAgreement()
    {
        $this->info("📋 Creando convenio de prueba...");
        
        $client = \App\Models\Client::where('xante_id', 'TESTFIX001')->first();
        
        if (!$client) {
            $this->error("  ❌ Cliente no encontrado");
            return;
        }

        // Crear convenio de prueba
        $agreement = \App\Models\Agreement::create([
            'client_xante_id' => $client->xante_id,
            'status' => 'draft',
            'current_step' => 1,
            'wizard_data' => ['client_id' => $client->id],
            'created_by' => 1
        ]);

        $this->line("  ✅ Convenio creado (ID: {$agreement->id})");
        $this->newLine();
        $this->info("🎯 Para probar el indicador:");
        $this->line("  1. Ve a: http://localhost:8000/admin/convenios/crear?agreement={$agreement->id}");
        $this->line("  2. Navega hasta el Paso 4 (Calculadora)");
        $this->line("  3. Deberías ver el indicador amarillo con:");
        $this->line("     • Ganancia Estimada: $557,191.70 (correcto)");
        $this->line("     • NO $557.00 (incorrecto)");
    }
}
