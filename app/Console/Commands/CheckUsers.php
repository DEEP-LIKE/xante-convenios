<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckUsers extends Command
{
    protected $signature = 'check:users';

    protected $description = 'Check existing users in database';

    public function handle()
    {
        $users = User::all(['id', 'name', 'email', 'role', 'created_at']);

        if ($users->isEmpty()) {
            $this->error('❌ No hay usuarios en la base de datos');
            $this->info('💡 Ejecuta: php artisan db:seed --class=UserSeeder');

            return;
        }

        $this->info('👥 USUARIOS EN LA BASE DE DATOS:');
        $this->info('');

        foreach ($users as $user) {
            $this->info("ID: {$user->id}");
            $this->info("Nombre: {$user->name}");
            $this->info("Email: {$user->email}");
            $this->info("Rol: {$user->role}");
            $this->info("Creado: {$user->created_at}");
            $this->info('---');
        }

        $this->info('');
        $this->info('🔐 CREDENCIALES ESPERADAS:');
        $this->info('   admin@xante.com / admin123');
        $this->info('   asesor@xante.com / asesor123');
        $this->info('   manager@xante.com / manager123');
    }
}
