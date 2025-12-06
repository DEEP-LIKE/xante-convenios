<?php

namespace Database\Seeders;

use App\Models\StateBankAccount;
use Illuminate\Database\Seeder;

class StateBankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Datos reales proporcionados por el cliente
        $accounts = [
            // Estado de México - Tecámac
            [
                'state_name' => 'Estado de México',
                'state_code' => 'MEX',
                'municipality' => 'Tecámac',
                'account_holder' => 'Xante & VI, SAPI de CV',
                'bank_name' => 'BBVA',
                'account_number' => '0154352572',
                'clabe' => '012180001543525726',
                'is_active' => true,
            ],
            // Hidalgo - Tula
            [
                'state_name' => 'Hidalgo',
                'state_code' => 'HGO',
                'municipality' => 'Tula',
                'account_holder' => 'Xante & VI, SAPI de CV',
                'bank_name' => 'BBVA',
                'account_number' => '0183189163',
                'clabe' => '012180001831891638',
                'is_active' => true,
            ],
            // Hidalgo - Pachuca
            [
                'state_name' => 'Hidalgo',
                'state_code' => 'HGO',
                'municipality' => 'Pachuca',
                'account_holder' => 'Xante & VI, SAPI de CV',
                'bank_name' => 'BBVA',
                'account_number' => '0154870212',
                'clabe' => '012180001548702120',
                'is_active' => true,
            ],
            // Querétaro
            [
                'state_name' => 'Querétaro',
                'state_code' => 'QRO',
                'municipality' => null,
                'account_holder' => 'Xante & VI, SAPI de CV',
                'bank_name' => 'BBVA',
                'account_number' => '0177112955',
                'clabe' => '012180001771129554',
                'is_active' => true,
            ],
            // Puebla
            [
                'state_name' => 'Puebla',
                'state_code' => 'PUE',
                'municipality' => null,
                'account_holder' => 'Xante & VI, SAPI de CV',
                'bank_name' => 'BBVA',
                'account_number' => '0108111332',
                'clabe' => '012180001081113328',
                'is_active' => true,
            ],
            // Quintana Roo - Cancún
            [
                'state_name' => 'Quintana Roo',
                'state_code' => 'QROO',
                'municipality' => 'Cancún',
                'account_holder' => 'Xante & VI, SAPI de CV',
                'bank_name' => 'BBVA',
                'account_number' => '0183189759',
                'clabe' => '012180001831897593',
                'is_active' => true,
            ],
        ];

        // Limpiar registros genéricos anteriores
        StateBankAccount::whereIn('state_code', ['MEX', 'HGO', 'QRO', 'PUE', 'QROO'])
            ->where('account_number', '0000000000')
            ->delete();

        // Insertar cuentas reales
        foreach ($accounts as $account) {
            StateBankAccount::updateOrCreate(
                [
                    'state_code' => $account['state_code'],
                    'municipality' => $account['municipality'],
                ],
                $account
            );
        }

        // Mantener registros genéricos para otros estados
        $otherStates = [
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
            ['name' => 'Michoacán', 'code' => 'MICH'],
            ['name' => 'Morelos', 'code' => 'MOR'],
            ['name' => 'Nayarit', 'code' => 'NAY'],
            ['name' => 'Nuevo León', 'code' => 'NL'],
            ['name' => 'Oaxaca', 'code' => 'OAX'],
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

        foreach ($otherStates as $state) {
            StateBankAccount::firstOrCreate(
                [
                    'state_code' => $state['code'],
                    'municipality' => null,
                ],
                [
                    'state_name' => $state['name'],
                    'account_holder' => 'XANTE & VI, S.A.P.I. DE C.V.',
                    'bank_name' => 'BBVA',
                    'account_number' => '0000000000',
                    'clabe' => '000000000000000000',
                    'is_active' => true,
                ]
            );
        }
    }
}
