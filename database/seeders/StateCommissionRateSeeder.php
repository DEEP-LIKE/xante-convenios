<?php

namespace Database\Seeders;

use App\Models\StateCommissionRate;
use Illuminate\Database\Seeder;

class StateCommissionRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Estados con municipios específicos (Hidalgo tiene 2 entradas)
        $statesWithMunicipality = [
            ['name' => 'Hidalgo', 'code' => 'HGO', 'municipality' => 'Pachuca', 'percentage' => 8.0],
            ['name' => 'Hidalgo', 'code' => 'HGO', 'municipality' => 'Tula', 'percentage' => 8.0],
        ];

        // Estados sin municipio específico
        $states = [
            ['name' => 'Aguascalientes', 'code' => 'AGS'],
            ['name' => 'Baja California', 'code' => 'BC'],
            ['name' => 'Baja California Sur', 'code' => 'BCS'],
            ['name' => 'Campeche', 'code' => 'CAM'],
            ['name' => 'Chiapas', 'code' => 'CHIS'],
            ['name' => 'Chihuahua', 'code' => 'CHIH'],
            ['name' => 'Ciudad de México', 'code' => 'CDMX'],
            ['name' => 'Coahuila', 'code' => 'COAH'],
            ['name' => 'Colima', 'code' => 'COL'],
            ['name' => 'Durango', 'code' => 'DGO'],
            ['name' => 'Guanajuato', 'code' => 'GTO'],
            ['name' => 'Guerrero', 'code' => 'GRO'],
            ['name' => 'Jalisco', 'code' => 'JAL'],
            ['name' => 'Estado de México', 'code' => 'MEX'],
            ['name' => 'Michoacán', 'code' => 'MICH'],
            ['name' => 'Morelos', 'code' => 'MOR'],
            ['name' => 'Nayarit', 'code' => 'NAY'],
            ['name' => 'Nuevo León', 'code' => 'NL'],
            ['name' => 'Oaxaca', 'code' => 'OAX'],
            ['name' => 'Puebla', 'code' => 'PUE'],
            ['name' => 'Querétaro', 'code' => 'QRO'],
            ['name' => 'Quintana Roo', 'code' => 'QROO'],
            ['name' => 'San Luis Potosí', 'code' => 'SLP'],
            ['name' => 'Sinaloa', 'code' => 'SIN'],
            ['name' => 'Sonora', 'code' => 'SON'],
            ['name' => 'Tabasco', 'code' => 'TAB'],
            ['name' => 'Tamaulipas', 'code' => 'TAM'],
            ['name' => 'Tlaxcala', 'code' => 'TLAX'],
            ['name' => 'Veracruz', 'code' => 'VER'],
            ['name' => 'Yucatán', 'code' => 'YUC'],
            ['name' => 'Zacatecas', 'code' => 'ZAC'],
        ];

        // Porcentajes específicos según requerimientos del cliente
        $statePercentages = [
            'Estado de México' => 9.5,
            'Querétaro' => 10.0,
            'Puebla' => 7.5,
            'Quintana Roo' => 8.0,
        ];

        // Solo estos estados tienen cuentas bancarias configuradas
        $statesWithBankAccounts = [
            'Estado de México',
            'Querétaro',
            'Puebla',
            'Quintana Roo',
        ];

        // Insertar estados con municipio (Hidalgo)
        foreach ($statesWithMunicipality as $state) {
            StateCommissionRate::updateOrCreate(
                [
                    'state_code' => $state['code'],
                    'municipality' => $state['municipality']
                ],
                [
                    'state_name' => $state['name'],
                    'commission_percentage' => $state['percentage'],
                    'is_active' => true, // Hidalgo tiene cuentas bancarias
                ]
            );
        }

        // Insertar estados sin municipio
        foreach ($states as $state) {
            StateCommissionRate::updateOrCreate(
                [
                    'state_code' => $state['code'],
                    'municipality' => null
                ],
                [
                    'state_name' => $state['name'],
                    'commission_percentage' => $statePercentages[$state['name']] ?? 5.00,
                    // Solo habilitar estados con cuentas bancarias
                    'is_active' => in_array($state['name'], $statesWithBankAccounts),
                ]
            );
        }
    }
}
