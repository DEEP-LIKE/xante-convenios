<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->string('type')->default('string'); // string, number, boolean, json
            $table->timestamps();
            
            // Índice
            $table->index('key');
        });

        // Insertar configuraciones por defecto
        DB::table('configurations')->insert([
            [
                'key' => 'comision_sin_iva_default',
                'value' => '6.5',
                'description' => 'Porcentaje de comisión sin IVA por defecto',
                'type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'comision_iva_incluido_default',
                'value' => '9',
                'description' => 'Porcentaje de comisión con IVA incluido por defecto',
                'type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'precio_promocion_multiplicador_default',
                'value' => '1.09',
                'description' => 'Multiplicador para calcular valor de convenio desde precio de promoción',
                'type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'isr_default',
                'value' => '0',
                'description' => 'ISR por defecto',
                'type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'cancelacion_hipoteca_default',
                'value' => '0',
                'description' => 'Monto de cancelación de hipoteca por defecto',
                'type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'monto_credito_default',
                'value' => '0',
                'description' => 'Monto de crédito por defecto',
                'type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
