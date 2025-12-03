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
        // El valor actual de 16 está incorrecto. Debería ser 7.54
        // que representa: 6.5% (comisión sin IVA) + 16% de IVA = 7.54%
        DB::table('configurations')
            ->where('key', 'comision_iva_incluido_default')
            ->update(['value' => '7.54']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('configurations')
            ->where('key', 'comision_iva_incluido_default')
            ->update(['value' => '16']);
    }
};
