<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Seeder
{
    public function run(): void
    {
        // No eliminar usuarios existentes, solo crear/actualizar los necesarios
        
        // Crear o actualizar usuario administrador
        $admin = User::updateOrCreate(
            ['email' => 'admin@xante.com'],
            [
                'name' => 'Administrador Xante',
                'email' => 'admin@xante.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Crear o actualizar usuario asesor
        $asesor = User::updateOrCreate(
            ['email' => 'asesor@xante.com'],
            [
                'name' => 'Asesor de Ventas',
                'email' => 'asesor@xante.com',
                'password' => Hash::make('asesor123'),
                'role' => 'sales',
                'email_verified_at' => now(),
            ]
        );

        // Crear o actualizar usuario manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@xante.com'],
            [
                'name' => 'Manager Xante',
                'email' => 'manager@xante.com',
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'email_verified_at' => now(),
            ]
        );

        echo "✅ Usuarios creados/actualizados exitosamente:\n";
        echo "   👤 admin@xante.com / admin123 (ID: {$admin->id})\n";
        echo "   👤 asesor@xante.com / asesor123 (ID: {$asesor->id})\n";
        echo "   👤 manager@xante.com / manager123 (ID: {$manager->id})\n";
        echo "\n🔐 Puedes usar cualquiera de estas credenciales para acceder.\n";
        echo "\n💡 Si ya existían usuarios con estos emails, se actualizaron las contraseñas.\n";
    }
}
