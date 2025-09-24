<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando población de base de datos...');
        
        $this->call([
            ConfigurationSeeder::class,
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
        $this->command->info('   🔐 manager@xante.com / manager123 (Manager)');
        $this->command->info('');
        $this->command->info('🎯 DATOS DE PRUEBA DISPONIBLES:');
        $this->command->info('   👥 3 Clientes con diferentes estados civiles');
        $this->command->info('   📄 3 Convenios en diferentes etapas del wizard');
        $this->command->info('   ⚙️  Configuraciones de calculadora financiera');
        $this->command->info('   🏠 Propiedades de ejemplo');
        $this->command->info('');
        $this->command->info('✨ ¡El sistema wizard está listo para usar!');
    }
}
