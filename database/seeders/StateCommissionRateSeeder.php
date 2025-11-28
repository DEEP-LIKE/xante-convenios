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
            ['name' => 'Hidalgo', 'code' => 'HGO'],
            ['name' => 'Jalisco', 'code' => 'JAL'],
            ['name' => 'México', 'code' => 'MEX'],
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

        foreach ($states as $state) {
            StateCommissionRate::updateOrCreate(
                ['state_code' => $state['code']],
                [
                    'state_name' => $state['name'],
                    'commission_percentage' => 5.00, // Valor por defecto
                    'is_active' => true,
                ]
            );
        }
    }
}
