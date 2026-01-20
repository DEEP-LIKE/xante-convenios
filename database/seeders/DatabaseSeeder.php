<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Iniciando población de base de datos...');

        $this->call([
            UserSeeder::class,
            ConfigurationSeeder::class,           // Agregado
            CalculatorConfigurationSeeder::class,  // Agregado
            StateCommissionRateSeeder::class,     // Agregado
            StateBankAccountSeeder::class,        // Agregado
            // ClientSeeder::class,
            PropertySeeder::class,
            AgreementSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('🎉 ¡Base de datos poblada exitosamente!');
        $this->command->info('');
        $this->command->info('📋 CREDENCIALES DE ACCESO:');
        $this->command->info('   🔐 gerencia@xante.com / Xante2025! (Gerencia)');
        $this->command->info('   🔐 coordinador@xante.com / Xante2025! (Coordinador FI)');
        $this->command->info('   🔐 ejecutivo@xante.com / Xante2025! (Ejecutivo)');
        $this->command->info('');
        $this->command->info('🎯 DATOS DE PRUEBA DISPONIBLES:');
        $this->command->info('   👥 3 Clientes (2 con cónyuge/pareja)');
        $this->command->info('   📄 3 Convenios en diferentes etapas');
        $this->command->info('   🏠 3 Propiedades de ejemplo');
        $this->command->info('');
        $this->command->info('✨ ¡El sistema está listo para usar!');
    }
}
