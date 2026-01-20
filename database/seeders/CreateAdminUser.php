<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Seeder
{
    public function run(): void
    {
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
                'role' => 'asesor',
                'email_verified_at' => now(),
            ]
        );

        // Crear o actualizar usuario viewer
        $viewer = User::updateOrCreate(
            ['email' => 'viewer@xante.com'],
            [
                'name' => 'Viewer Xante',
                'email' => 'viewer@xante.com',
                'password' => Hash::make('viewer123'),
                'role' => 'viewer',
                'email_verified_at' => now(),
            ]
        );

        echo "✅ Usuarios creados/actualizados exitosamente:\n";
        echo "   👤 admin@xante.com / admin123 (ID: {$admin->id})\n";
        echo "   👤 asesor@xante.com / asesor123 (ID: {$asesor->id})\n";
        echo "   👤 viewer@xante.com / viewer123 (ID: {$viewer->id})\n";
        echo "\n🔐 Puedes usar cualquiera de estas credenciales para acceder.\n";
    }
}
