<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'xante_id' => 'XNT001',
                'name' => 'Juan Carlos Pérez García',
                'email' => 'juan.perez@example.com',
                'phone' => '+52 55 1234-5678',
                // Campos adicionales del wizard
                'delivery_file' => 'Oficina Central',
                'civil_status' => 'casado',
                'regime_type' => 'sociedad_conyugal',
                'occupation' => 'Ingeniero de Sistemas',
                'office_phone' => '+52 55 1234-5679',
                'additional_contact_phone' => '+52 55 1234-5680',
                'neighborhood' => 'Del Valle',
                'postal_code' => '03100',
                // Datos del cónyuge
                'spouse_name' => 'Ana María Pérez Sánchez',
                'spouse_email' => 'ana.perez@example.com',
                'spouse_phone' => '+52 55 1234-5681',
                'spouse_civil_status' => 'casado',
                'spouse_occupation' => 'Contadora Pública',
            ],
            [
                'xante_id' => 'XNT002',
                'name' => 'María Elena Rodríguez López',
                'email' => 'maria.rodriguez@example.com',
                'phone' => '+52 55 9876-5432',
                // Campos adicionales del wizard
                'delivery_file' => 'Sucursal Norte',
                'civil_status' => 'soltero',
                'regime_type' => 'no_aplica',
                'occupation' => 'Doctora',
                'office_phone' => '+52 55 9876-5433',
                'additional_contact_phone' => '+52 55 9876-5434',
                'neighborhood' => 'Juárez',
                'postal_code' => '06600',
            ],
            [
                'xante_id' => 'XNT003',
                'name' => 'Roberto Martínez Hernández',
                'email' => 'roberto.martinez@example.com',
                'phone' => '+52 55 5555-1234',
                // Campos adicionales del wizard
                'delivery_file' => 'Oficina Sur',
                'civil_status' => 'union_libre',
                'regime_type' => 'separacion_bienes',
                'occupation' => 'Arquitecto',
                'office_phone' => '+52 55 5555-1235',
                'additional_contact_phone' => '+52 55 5555-1236',
                'neighborhood' => 'Lomas de Chapultepec',
                'postal_code' => '11000',
                // Datos del cónyuge
                'spouse_name' => 'Claudia Hernández Torres',
                'spouse_email' => 'claudia.hernandez@example.com',
                'spouse_phone' => '+52 55 5555-1237',
                'spouse_civil_status' => 'union_libre',
                'spouse_occupation' => 'Diseñadora Gráfica',
                // Contactos AC
                'ac_name' => 'Administración Central Lomas',
                'ac_phone' => '+52 55 5555-9999',
                'ac_quota' => 2500.00,
            ],
        ];

        foreach ($clients as $clientData) {
            Client::updateOrCreate(
                ['xante_id' => $clientData['xante_id']],
                $clientData
            );
        }

        $this->command->info('✅ Clientes de prueba creados exitosamente:');
        $this->command->info('   👤 XNT001 - Juan Carlos Pérez García (Casado)');
        $this->command->info('   👤 XNT002 - María Elena Rodríguez López (Soltero)');
        $this->command->info('   👤 XNT003 - Roberto Martínez Hernández (Unión Libre)');
    }
}
