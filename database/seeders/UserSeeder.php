<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        // Usuario Administrador
        User::updateOrCreate(
            ['email' => 'admin@xante.com'],
            [
                'name' => 'Administrador Xante',
                'email' => 'admin@xante.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Usuario Asesor de Ventas
        User::updateOrCreate(
            ['email' => 'asesor@xante.com'],
            [
                'name' => 'Asesor de Ventas',
                'email' => 'asesor@xante.com',
                'password' => Hash::make('asesor123'),
                'role' => 'sales',
                'email_verified_at' => now(),
            ]
        );

        // Usuario Manager
        User::updateOrCreate(
            ['email' => 'manager@xante.com'],
            [
                'name' => 'Manager Xante',
                'email' => 'manager@xante.com',
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Usuarios creados exitosamente:');
        $this->command->info('   👤 admin@xante.com / admin123 (Administrador)');
        $this->command->info('   👤 asesor@xante.com / asesor123 (Asesor de Ventas)');
        $this->command->info('   👤 manager@xante.com / manager123 (Manager)');
    }
}
