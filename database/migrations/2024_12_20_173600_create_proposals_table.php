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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('idxante')->nullable()->index()->comment('ID Xante del cliente para enlace');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null')->comment('Referencia al cliente');
            $table->json('data')->comment('Datos completos del cálculo financiero');
            $table->boolean('linked')->default(false)->index()->comment('Si la propuesta está enlazada a un cliente');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade')->comment('Usuario que creó la propuesta');
            $table->timestamps();

            // Índices para optimizar búsquedas
            $table->index(['idxante', 'linked']);
            $table->index(['client_id', 'created_at']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
