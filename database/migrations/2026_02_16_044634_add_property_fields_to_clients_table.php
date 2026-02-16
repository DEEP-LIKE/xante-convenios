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
            $table->string('domicilio_convenio')->nullable()->after('state');
            $table->string('comunidad')->nullable()->after('domicilio_convenio');
            $table->string('tipo_vivienda')->nullable()->after('comunidad');
            $table->string('prototipo')->nullable()->after('tipo_vivienda');
            $table->string('lote')->nullable()->after('prototipo');
            $table->string('manzana')->nullable()->after('lote');
            $table->string('etapa')->nullable()->after('manzana');
            $table->string('municipio_propiedad')->nullable()->after('etapa');
            $table->string('estado_propiedad')->nullable()->after('municipio_propiedad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'domicilio_convenio',
                'comunidad',
                'tipo_vivienda',
                'prototipo',
                'lote',
                'manzana',
                'etapa',
                'municipio_propiedad',
                'estado_propiedad',
            ]);
        });
    }
};
