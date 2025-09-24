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
        Schema::table('agreements', function (Blueprint $table) {
            // Datos y valor vivienda
            $table->decimal('precio_promocion', 12, 2)->nullable();
            $table->string('domicilio_vivienda')->nullable();
            $table->string('comunidad')->nullable();
            $table->string('tipo_vivienda')->nullable();
            $table->string('prototipo')->nullable();
            $table->decimal('valor_convenio', 12, 2)->nullable();
            $table->decimal('monto_credito', 12, 2)->nullable();
            $table->string('tipo_credito')->nullable();
            $table->string('otro_banco')->nullable();
            
            // Comisiones
            $table->decimal('porcentaje_comision_sin_iva', 5, 2)->nullable();
            $table->decimal('comision_iva_incluido', 5, 2)->nullable();
            $table->decimal('monto_comision_sin_iva', 12, 2)->nullable();
            $table->decimal('comision_total_pagar', 12, 2)->nullable();
            
            // Costos de operación
            $table->decimal('valor_compraventa', 12, 2)->nullable();
            $table->decimal('comision_total', 12, 2)->nullable();
            $table->decimal('ganancia_final', 12, 2)->nullable();
            $table->decimal('isr', 12, 2)->nullable();
            $table->decimal('cancelacion_hipoteca', 12, 2)->nullable();
            $table->decimal('total_gastos_fi', 12, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn([
                'precio_promocion', 'domicilio_vivienda', 'comunidad', 'tipo_vivienda', 'prototipo',
                'valor_convenio', 'monto_credito', 'tipo_credito', 'otro_banco',
                'porcentaje_comision_sin_iva', 'comision_iva_incluido', 'monto_comision_sin_iva', 
                'comision_total_pagar', 'valor_compraventa', 'comision_total', 'ganancia_final',
                'isr', 'cancelacion_hipoteca', 'total_gastos_fi'
            ]);
        });
    }
};
