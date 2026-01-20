<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('idxante')->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Valores calculados
            $table->decimal('valor_convenio', 15, 2)->nullable();
            $table->decimal('comision_total', 15, 2)->nullable();
            $table->decimal('ganancia_final', 15, 2)->nullable();

            // Parámetros de cálculo
            $table->decimal('porcentaje_comision', 5, 2)->nullable();
            $table->decimal('porcentaje_iva', 5, 2)->nullable();
            $table->integer('numero_parcialidades')->nullable();

            // Datos completos del cálculo (JSON solo para histórico)
            $table->json('calculation_data')->nullable();

            $table->timestamps();

            // Índices
            $table->index('client_id');
            $table->index('idxante');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
