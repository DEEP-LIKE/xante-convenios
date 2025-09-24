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
        // Cambiar el enum de status para incluir los nuevos valores
        DB::statement("ALTER TABLE agreements MODIFY COLUMN status ENUM('sin_convenio', 'expediente_incompleto', 'expediente_completo', 'convenio_proceso', 'convenio_firmado') DEFAULT 'expediente_incompleto'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al enum original
        DB::statement("ALTER TABLE agreements MODIFY COLUMN status ENUM('iniciado', 'pendiente_docs', 'completado') DEFAULT 'iniciado'");
    }
};
