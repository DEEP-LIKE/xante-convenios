<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar columnas de propiedad a la tabla agreements.
     * Estas columnas permiten sincronizar datos de propiedad directamente
     * desde el wizard (paso 3) sin depender exclusivamente de wizard_data JSON.
     */
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $afterColumn = 'wizard_data';

            $columns = [
                'domicilio_convenio',
                'comunidad',
                'tipo_vivienda',
                'prototipo',
                'lote',
                'manzana',
                'etapa',
                'municipio_propiedad',
                'estado_propiedad',
                'hipotecado',
                'tipo_hipoteca',
                'niveles',
            ];

            foreach ($columns as $column) {
                if (! Schema::hasColumn('agreements', $column)) {
                    $table->string($column)->nullable()->after($afterColumn);
                    $afterColumn = $column; // Mantiene el orden
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $columns = [
                'domicilio_convenio', 'comunidad', 'tipo_vivienda', 'prototipo',
                'lote', 'manzana', 'etapa', 'municipio_propiedad', 
                'estado_propiedad', 'hipotecado', 'tipo_hipoteca', 'niveles',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('agreements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
