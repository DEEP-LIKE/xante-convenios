<?php

namespace Database\Seeders;

use App\Models\Agreement;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgreementSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::all();
        $users = User::all();

        if ($clients->isEmpty() || $users->isEmpty()) {
            $this->command->warn('⚠️  No hay clientes o usuarios disponibles. Ejecuta primero UserSeeder y ClientSeeder.');
            return;
        }

        $agreements = [
            [
                'client_xante_id' => 'XNT001',
                'current_step' => 3,
                'status' => 'expediente_incompleto',
                'completion_percentage' => 45,
                'precio_promocion' => 1629550.00,
                'valor_convenio' => 1776209.50,
                'porcentaje_comision_sin_iva' => 6.50,
                'monto_credito' => 800000.00,
                'tipo_credito' => 'BANCARIO',
                'isr' => 0.00,
                'cancelacion_hipoteca' => 20000.00,
                'total_gastos_fi' => 20000.00,
                'ganancia_final' => 1602305.10,
                'wizard_data' => json_encode([
                    'property_address' => 'Residencial Los Pinos, Casa 123',
                    'property_development' => 'Los Pinos Residencial',
                    'property_type' => 'Casa',
                    'property_prototype' => 'Modelo Ejecutivo',
                    'documents_checklist' => [
                        'titular_ine' => true,
                        'titular_curp' => true,
                        'titular_rfc' => false,
                    ]
                ]),
            ],
            [
                'client_xante_id' => 'XNT002',
                'current_step' => 5,
                'status' => 'convenio_proceso',
                'completion_percentage' => 80,
                'precio_promocion' => 2100000.00,
                'valor_convenio' => 2289000.00,
                'porcentaje_comision_sin_iva' => 6.50,
                'monto_credito' => 1200000.00,
                'tipo_credito' => 'INFONAVIT',
                'isr' => 5000.00,
                'cancelacion_hipoteca' => 0.00,
                'total_gastos_fi' => 25000.00,
                'ganancia_final' => 2086394.00,
                'wizard_data' => json_encode([
                    'property_address' => 'Torre Ejecutiva, Depto 1205',
                    'property_development' => 'Torre Ejecutiva',
                    'property_type' => 'Departamento',
                    'property_prototype' => 'Modelo Premium',
                    'documents_checklist' => [
                        'titular_ine' => true,
                        'titular_curp' => true,
                        'titular_rfc' => true,
                        'propiedad_instrumento_notarial' => true,
                    ]
                ]),
            ],
            [
                'client_xante_id' => 'XNT003',
                'current_step' => 6,
                'status' => 'convenio_firmado',
                'completion_percentage' => 100,
                'precio_promocion' => 1850000.00,
                'valor_convenio' => 2016500.00,
                'porcentaje_comision_sin_iva' => 6.50,
                'monto_credito' => 950000.00,
                'tipo_credito' => 'BANCARIO',
                'isr' => 3500.00,
                'cancelacion_hipoteca' => 15000.00,
                'total_gastos_fi' => 22000.00,
                'ganancia_final' => 1824244.00,
                'completed_at' => now()->subDays(2),
                'wizard_data' => json_encode([
                    'property_address' => 'Villas del Sol, Villa 45',
                    'property_development' => 'Villas del Sol',
                    'property_type' => 'Villa',
                    'property_prototype' => 'Modelo Familiar',
                    'documents_checklist' => [
                        'titular_ine' => true,
                        'titular_curp' => true,
                        'titular_rfc' => true,
                        'conyuge_ine' => true,
                        'conyuge_curp' => true,
                        'propiedad_instrumento_notarial' => true,
                        'propiedad_traslado_dominio' => true,
                        'otros_autorizacion_buro' => true,
                    ]
                ]),
            ],
        ];

        foreach ($agreements as $agreementData) {
            // Asignar usuario aleatorio
            $agreementData['created_by'] = $users->random()->id;
            $agreementData['assigned_to'] = $users->random()->id;

            Agreement::updateOrCreate(
                ['client_xante_id' => $agreementData['client_xante_id']],
                $agreementData
            );
        }

        $this->command->info('✅ Convenios de prueba creados exitosamente:');
        $this->command->info('   📄 XNT001 - Convenio en Paso 3 (Expediente Incompleto)');
        $this->command->info('   📄 XNT002 - Convenio en Paso 5 (Convenio en Proceso)');
        $this->command->info('   📄 XNT003 - Convenio Completado (Convenio Firmado)');
    }
}
