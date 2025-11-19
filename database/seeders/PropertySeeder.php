<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        // Las propiedades se crean en AgreementSeeder
        // Este seeder existe solo para compatibilidad
        $this->command->info('✅ PropertySeeder ejecutado (propiedades creadas en AgreementSeeder)');
    }
}
