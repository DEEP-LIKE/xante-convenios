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
        Schema::create('agreement_recalculations', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Usuario que hizo el cambio

            // Contador secuencial por convenio (#1, #2, etc)
            $table->integer('recalculation_number')->default(1);

            // Valores financieros clave (snapshot)
            $table->decimal('agreement_value', 15, 2)->nullable(); // Valor Convenio
            $table->decimal('proposal_value', 15, 2)->nullable();  // Precio Promoción
            $table->decimal('commission_total', 15, 2)->nullable(); // Comisión Total
            $table->decimal('final_profit', 15, 2)->nullable();    // Ganancia Final

            // JSON para guardar TODOS los variables del cálculo (snapshot completo)
            // Esto permite reconstruir el estado exacto de la calculadora
            $table->json('calculation_data')->nullable();

            // Auditoría
            $table->text('motivo'); // Razón del cambio
            
            $table->timestamps();

            // Índices
            $table->index(['agreement_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreement_recalculations');
    }
};
