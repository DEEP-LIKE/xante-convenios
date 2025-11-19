<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Spouse;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        // Cliente 1 - Casado (con cónyuge)
        $client1 = Client::updateOrCreate(
            ['xante_id' => 'XNT001'],
            [
                'xante_id' => 'XNT001',
                'name' => 'Juan Carlos Pérez García',
                'email' => 'juan.perez@example.com',
                'phone' => '+52 55 1234-5678',
                'birthdate' => '1985-03-15',
                'curp' => 'PEGJ850315HDFRRL01',
                'rfc' => 'PEGJ850315ABC',
                'delivery_file' => 'Oficina Central',
                'civil_status' => 'casado',
                'occupation' => 'Ingeniero de Sistemas',
                'office_phone' => '+52 55 1234-5679',
                'additional_contact_phone' => '+52 55 1234-5680',
                'current_address' => 'Av. Insurgentes Sur 1234, Col. Del Valle',
                'neighborhood' => 'Del Valle',
                'postal_code' => '03100',
                'municipality' => 'Benito Juárez',
                'state' => 'Ciudad de México',
            ]
        );

        // Crear cónyuge para cliente 1
        Spouse::updateOrCreate(
            ['client_id' => $client1->id],
            [
                'client_id' => $client1->id,
                'name' => 'Ana María Pérez Sánchez',
                'email' => 'ana.perez@example.com',
                'phone' => '+52 55 1234-5681',
                'birthdate' => '1987-07-20',
                'curp' => 'PESA870720MDFRRN02',
                'rfc' => 'PESA870720XYZ',
                'civil_status' => 'casado',
                'regime_type' => 'sociedad_conyugal',
                'occupation' => 'Contadora Pública',
                'current_address' => 'Av. Insurgentes Sur 1234, Col. Del Valle',
                'neighborhood' => 'Del Valle',
                'postal_code' => '03100',
                'municipality' => 'Benito Juárez',
                'state' => 'Ciudad de México',
            ]
        );

        // Cliente 2 - Soltero (sin cónyuge)
        Client::updateOrCreate(
            ['xante_id' => 'XNT002'],
            [
                'xante_id' => 'XNT002',
                'name' => 'María Elena Rodríguez López',
                'email' => 'maria.rodriguez@example.com',
                'phone' => '+52 55 9876-5432',
                'birthdate' => '1990-11-08',
                'curp' => 'ROLM901108MDFDRR03',
                'rfc' => 'ROLM901108DEF',
                'delivery_file' => 'Sucursal Norte',
                'civil_status' => 'soltero',
                'occupation' => 'Doctora',
                'office_phone' => '+52 55 9876-5433',
                'additional_contact_phone' => '+52 55 9876-5434',
                'current_address' => 'Paseo de la Reforma 456, Col. Juárez',
                'neighborhood' => 'Juárez',
                'postal_code' => '06600',
                'municipality' => 'Cuauhtémoc',
                'state' => 'Ciudad de México',
            ]
        );

        // Cliente 3 - Unión libre (con pareja)
        $client3 = Client::updateOrCreate(
            ['xante_id' => 'XNT003'],
            [
                'xante_id' => 'XNT003',
                'name' => 'Roberto Martínez Hernández',
                'email' => 'roberto.martinez@example.com',
                'phone' => '+52 55 5555-1234',
                'birthdate' => '1982-05-25',
                'curp' => 'MAHR820525HDFRRB04',
                'rfc' => 'MAHR820525GHI',
                'delivery_file' => 'Oficina Sur',
                'civil_status' => 'union_libre',
                'occupation' => 'Arquitecto',
                'office_phone' => '+52 55 5555-1235',
                'additional_contact_phone' => '+52 55 5555-1236',
                'current_address' => 'Lomas de Chapultepec 789',
                'neighborhood' => 'Lomas de Chapultepec',
                'postal_code' => '11000',
                'municipality' => 'Miguel Hidalgo',
                'state' => 'Ciudad de México',
            ]
        );

        // Crear pareja para cliente 3
        Spouse::updateOrCreate(
            ['client_id' => $client3->id],
            [
                'client_id' => $client3->id,
                'name' => 'Claudia Hernández Torres',
                'email' => 'claudia.hernandez@example.com',
                'phone' => '+52 55 5555-1237',
                'birthdate' => '1984-09-12',
                'curp' => 'HETC840912MDFRRR05',
                'rfc' => 'HETC840912JKL',
                'civil_status' => 'union_libre',
                'regime_type' => 'separacion_bienes',
                'occupation' => 'Diseñadora Gráfica',
                'current_address' => 'Lomas de Chapultepec 789',
                'neighborhood' => 'Lomas de Chapultepec',
                'postal_code' => '11000',
                'municipality' => 'Miguel Hidalgo',
                'state' => 'Ciudad de México',
            ]
        );

        $this->command->info('✅ Clientes de prueba creados exitosamente:');
        $this->command->info('   👤 XNT001 - Juan Carlos Pérez García (Casado, con cónyuge)');
        $this->command->info('   👤 XNT002 - María Elena Rodríguez López (Soltero)');
        $this->command->info('   👤 XNT003 - Roberto Martínez Hernández (Unión Libre, con pareja)');
    }
}
