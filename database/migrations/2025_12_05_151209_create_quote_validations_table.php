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
        Schema::create('quote_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected', 'with_observations'])->default('pending');
            $table->text('observations')->nullable();
            $table->json('calculator_snapshot')->nullable(); // Snapshot de la calculadora al momento de solicitar
            $table->timestamp('validated_at')->nullable();
            $table->integer('revision_number')->default(1); // Para tracking de revisiones
            $table->timestamps();
            
            // Índices para mejorar performance
            $table->index(['agreement_id', 'status']);
            $table->index('requested_by');
            $table->index('validated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_validations');
    }
};
