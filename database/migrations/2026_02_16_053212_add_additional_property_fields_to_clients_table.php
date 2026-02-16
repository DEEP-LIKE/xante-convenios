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
            $table->string('hipotecado')->nullable()->after('estado_propiedad');
            $table->string('tipo_hipoteca')->nullable()->after('hipotecado');
            $table->integer('niveles')->nullable()->after('tipo_hipoteca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['hipotecado', 'tipo_hipoteca', 'niveles']);
        });
    }
};
