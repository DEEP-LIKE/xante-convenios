<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // xante_id ya se agregó en la migración anterior, no lo agregamos aquí
            
            // Eliminar campos que ahora van en agreements
            $table->dropColumn([
                'birthdate',
                'curp',
                'rfc',
                'current_address',
                'municipality',
                'state',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Restaurar campos eliminados
            $table->date('birthdate')->nullable();
            $table->string('curp', 18)->nullable();
            $table->string('rfc', 13)->nullable();
            $table->text('current_address')->nullable();
            $table->string('municipality')->nullable();
            $table->string('state')->nullable();
            
            // xante_id se maneja en la migración anterior, no lo eliminamos aquí
        });
    }
};
