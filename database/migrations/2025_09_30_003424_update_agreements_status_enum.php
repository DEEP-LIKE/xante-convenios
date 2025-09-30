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
            // Actualizar el enum de status para incluir todos los nuevos estados
            $table->enum('status', [
                // Estados originales
                'sin_convenio',
                'expediente_incompleto', 
                'expediente_completo',
                'convenio_proceso',
                'convenio_firmado',
                // Nuevos estados del sistema de dos wizards
                'draft',
                'pending_validation',
                'documents_generating',
                'documents_generated',
                'documents_sent',
                'awaiting_client_docs',
                'documents_complete',
                'completed',
                'error_generating_documents'
            ])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            // Revertir al enum original
            $table->enum('status', [
                'sin_convenio',
                'expediente_incompleto', 
                'expediente_completo',
                'convenio_proceso',
                'convenio_firmado'
            ])->default('sin_convenio')->change();
        });
    }
};
