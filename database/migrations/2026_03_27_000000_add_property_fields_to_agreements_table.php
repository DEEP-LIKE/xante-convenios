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
            // Solo agregar columnas que no existan ya
            if (! Schema::hasColumn('agreements', 'lote')) {
                $table->string('lote')->nullable()->after('prototipo');
            }
            if (! Schema::hasColumn('agreements', 'manzana')) {
                $table->string('manzana')->nullable()->after('lote');
            }
            if (! Schema::hasColumn('agreements', 'etapa')) {
                $table->string('etapa')->nullable()->after('manzana');
            }
            if (! Schema::hasColumn('agreements', 'municipio_propiedad')) {
                $table->string('municipio_propiedad')->nullable()->after('etapa');
            }
            if (! Schema::hasColumn('agreements', 'estado_propiedad')) {
                $table->string('estado_propiedad')->nullable()->after('municipio_propiedad');
            }
            if (! Schema::hasColumn('agreements', 'hipotecado')) {
                $table->string('hipotecado')->nullable()->after('estado_propiedad');
            }
            if (! Schema::hasColumn('agreements', 'tipo_hipoteca')) {
                $table->string('tipo_hipoteca')->nullable()->after('hipotecado');
            }
            if (! Schema::hasColumn('agreements', 'niveles')) {
                $table->string('niveles')->nullable()->after('tipo_hipoteca');
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
                'lote', 'manzana', 'etapa',
                'municipio_propiedad', 'estado_propiedad',
                'hipotecado', 'tipo_hipoteca', 'niveles',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('agreements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
