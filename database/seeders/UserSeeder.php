<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador
        User::updateOrCreate(
            ['email' => 'admin@xante.com'],
            [
                'name' => 'Administrador',
                'email' => 'admin@xante.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Crear usuario asesor de ejemplo
        User::updateOrCreate(
            ['email' => 'asesor@xante.com'],
            [
                'name' => 'Asesor Demo',
                'email' => 'asesor@xante.com',
                'password' => Hash::make('asesor123'),
                'role' => 'asesor',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Usuarios creados exitosamente:');
        $this->command->info('- admin@xante.com / admin123 (Administrador)');
        $this->command->info('- asesor@xante.com / asesor123 (Asesor)');
    }
}
