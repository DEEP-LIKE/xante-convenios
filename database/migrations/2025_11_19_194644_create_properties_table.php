<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            
            // Ubicación principal
            $table->text('address');
            $table->string('community')->nullable();
            $table->string('municipality');
            $table->string('state');
            $table->string('postal_code')->nullable();
            
            // Detalles de la propiedad
            $table->string('property_type')->nullable();
            $table->string('prototype')->nullable();
            $table->string('lot')->nullable();
            $table->string('block')->nullable();
            $table->string('stage')->nullable();
            
            // Documentación legal
            $table->date('deed_date')->nullable();
            $table->string('registry_data')->nullable();
            $table->text('notarial_instrument_notes')->nullable();
            
            // Contactos de la comunidad/privada
            $table->string('ac_name')->nullable();
            $table->string('ac_phone')->nullable();
            $table->decimal('ac_quota', 10, 2)->nullable();
            $table->string('president_name')->nullable();
            $table->string('president_phone')->nullable();
            $table->decimal('president_quota', 10, 2)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['municipality', 'state']);
            $table->index('community');
            $table->index('property_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
