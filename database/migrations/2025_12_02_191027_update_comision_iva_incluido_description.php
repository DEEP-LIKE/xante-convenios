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
        DB::table('configurations')
            ->where('key', 'comision_iva_incluido_default')
            ->update(['description' => 'Comisión (IVA incluido) - Incluye 16% de IVA']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('configurations')
            ->where('key', 'comision_iva_incluido_default')
            ->update(['description' => 'Porcentaje de comisión con IVA incluido por defecto']);
    }
};
