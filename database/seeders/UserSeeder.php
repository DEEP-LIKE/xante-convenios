<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Gerencia Xante',
                'email' => 'gerencia@xante.com',
                'password' => Hash::make('Xante2025!'),
                'role' => 'gerencia',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Coordinador FI',
                'email' => 'coordinador@xante.com',
                'password' => Hash::make('Xante2025!'),
                'role' => 'coordinador_fi',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Ejecutivo Demo',
                'email' => 'ejecutivo@xante.com',
                'password' => Hash::make('Xante2025!'),
                'role' => 'ejecutivo',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Admin Carbono',
                'email' => 'admin@carbono.mx',
                'password' => Hash::make('Carbono2025!'),
                'role' => 'gerencia',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Usuario Vinte',
                'email' => 'usuario@vinte.com',
                'password' => Hash::make('Vinte2025!'),
                'role' => 'ejecutivo',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
