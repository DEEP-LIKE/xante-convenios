<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $keysToDelete = [
            'precio_promocion_multiplicador_default',
            'precio_promocion_multiplier',
            'valor_convenio_default',
            'domicilio_convenio_default',
            'comunidad_default',
            'tipo_vivienda_default',
            'prototipo_default',
            'cancelacion_hipoteca_default',
            'monto_credito_default',
            'iva_multiplier',
            'comision_iva_incluido_default',
            'isr_default',
            'tipo_credito_default',
            'total_gastos_fi_default',
            'valor_otro_banco_default',
            'iva_percentage_default',
            'otro_banco_default',
        ];

        DB::table('configurations')->whereIn('key', $keysToDelete)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible automáticamente sin los valores originales.
        // Se espera que los seeders manejen los valores correctos de ahora en adelante.
    }
};
