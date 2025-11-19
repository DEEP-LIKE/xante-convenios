<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Iniciando población de base de datos...');
        
        $this->call([
            UserSeeder::class,
            ClientSeeder::class,
            PropertySeeder::class,
            AgreementSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('🎉 ¡Base de datos poblada exitosamente!');
        $this->command->info('');
        $this->command->info('📋 CREDENCIALES DE ACCESO:');
        $this->command->info('   🔐 admin@xante.com / admin123 (Administrador)');
        $this->command->info('   🔐 asesor@xante.com / asesor123 (Asesor de Ventas)');
        $this->command->info('   🔐 viewer@xante.com / viewer123 (Viewer)');
        $this->command->info('');
        $this->command->info('🎯 DATOS DE PRUEBA DISPONIBLES:');
        $this->command->info('   👥 3 Clientes (2 con cónyuge/pareja)');
        $this->command->info('   📄 3 Convenios en diferentes etapas');
        $this->command->info('   🏠 3 Propiedades de ejemplo');
        $this->command->info('');
        $this->command->info('✨ ¡El sistema está listo para usar!');
    }
}
