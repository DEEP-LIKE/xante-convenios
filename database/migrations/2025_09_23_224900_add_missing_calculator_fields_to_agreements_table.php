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
            // Campos faltantes de la calculadora
            $table->string('domicilio_convenio')->nullable()->after('prototipo');
            $table->string('indicador_ganancia')->nullable()->after('total_gastos_fi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn([
                'domicilio_convenio',
                'indicador_ganancia'
            ]);
        });
    }
};
