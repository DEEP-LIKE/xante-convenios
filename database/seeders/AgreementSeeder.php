<?php

namespace Database\Seeders;

use App\Models\Agreement;
use App\Models\Client;
use App\Models\Property;
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

        // Crear propiedades primero
        $property1 = Property::create([
            'address' => 'Residencial Los Pinos, Casa 123',
            'community' => 'Los Pinos Residencial',
            'municipality' => 'Benito Juárez',
            'state' => 'Ciudad de México',
            'postal_code' => '03100',
            'property_type' => 'Casa',
            'prototype' => 'Modelo Ejecutivo',
            'lot' => '123',
            'block' => 'A',
            'stage' => 'Etapa 1',
        ]);

        $property2 = Property::create([
            'address' => 'Torre Ejecutiva, Depto 1205',
            'community' => 'Torre Ejecutiva',
            'municipality' => 'Cuauhtémoc',
            'state' => 'Ciudad de México',
            'postal_code' => '06600',
            'property_type' => 'Departamento',
            'prototype' => 'Modelo Premium',
        ]);

        $property3 = Property::create([
            'address' => 'Villas del Sol, Villa 45',
            'community' => 'Villas del Sol',
            'municipality' => 'Miguel Hidalgo',
            'state' => 'Ciudad de México',
            'postal_code' => '11000',
            'property_type' => 'Villa',
            'prototype' => 'Modelo Familiar',
            'lot' => '45',
            'block' => 'B',
        ]);

        // Convenio 1 - En proceso (paso 3)
        $client1 = $clients->where('xante_id', 'XNT001')->first();
        Agreement::create([
            'client_id' => $client1->id,
            'property_id' => $property1->id,
            'spouse_id' => $client1->spouse?->id,
            'created_by' => $users->first()->id,
            'assigned_to' => $users->first()->id,
            'status' => 'draft',
            'current_wizard' => 1,
            'current_step' => 3,
            'completion_percentage' => 45,
            'agreement_value' => 1776209.50,
            'wizard_data' => json_encode([
                'valor_convenio' => 1776209.50,
                'precio_promocion' => 1629550.00,
                'porcentaje_comision_sin_iva' => 6.50,
                'monto_credito' => 800000.00,
                'tipo_credito' => 'BANCARIO',
            ]),
        ]);

        // Convenio 2 - Documentos generados
        $client2 = $clients->where('xante_id', 'XNT002')->first();
        Agreement::create([
            'client_id' => $client2->id,
            'property_id' => $property2->id,
            'created_by' => $users->first()->id,
            'assigned_to' => $users->first()->id,
            'status' => 'documents_generated',
            'current_wizard' => 1,
            'current_step' => 5,
            'completion_percentage' => 80,
            'agreement_value' => 2289000.00,
            'documents_generated_at' => now()->subDays(1),
            'wizard_data' => json_encode([
                'valor_convenio' => 2289000.00,
                'precio_promocion' => 2100000.00,
                'porcentaje_comision_sin_iva' => 6.50,
                'monto_credito' => 1200000.00,
                'tipo_credito' => 'INFONAVIT',
            ]),
        ]);

        // Convenio 3 - Completado
        $client3 = $clients->where('xante_id', 'XNT003')->first();
        Agreement::create([
            'client_id' => $client3->id,
            'property_id' => $property3->id,
            'spouse_id' => $client3->spouse?->id,
            'created_by' => $users->first()->id,
            'assigned_to' => $users->first()->id,
            'status' => 'completed',
            'current_wizard' => 2,
            'current_step' => 5,
            'wizard2_current_step' => 3,
            'completion_percentage' => 100,
            'agreement_value' => 2016500.00,
            'proposal_value' => 1950000.00,
            'documents_generated_at' => now()->subDays(5),
            'documents_sent_at' => now()->subDays(4),
            'documents_received_at' => now()->subDays(3),
            'completed_at' => now()->subDays(2),
            'wizard_data' => json_encode([
                'valor_convenio' => 2016500.00,
                'precio_promocion' => 1850000.00,
                'porcentaje_comision_sin_iva' => 6.50,
                'monto_credito' => 950000.00,
                'tipo_credito' => 'BANCARIO',
            ]),
        ]);

        $this->command->info('✅ Convenios de prueba creados exitosamente:');
        $this->command->info('   📄 Convenio 1 - En Paso 3 (Draft)');
        $this->command->info('   📄 Convenio 2 - Documentos Generados');
        $this->command->info('   📄 Convenio 3 - Completado');
    }
}
