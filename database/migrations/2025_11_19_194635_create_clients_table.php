<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // IDs externos
            $table->string('xante_id')->unique()->nullable();
            $table->string('hubspot_id')->unique()->nullable();

            // Datos personales básicos
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('curp', 18)->unique()->nullable();
            $table->string('rfc', 13)->unique()->nullable();

            // Datos adicionales
            $table->string('civil_status')->nullable();
            $table->string('occupation')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('additional_contact_phone')->nullable();
            $table->string('delivery_file')->nullable();

            // Dirección completa
            $table->text('current_address')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('municipality')->nullable();
            $table->string('state')->nullable();

            // Metadata
            $table->date('fecha_registro')->nullable();
            $table->timestamp('hubspot_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices estratégicos
            $table->index('email');
            $table->index('curp');
            $table->index('rfc');
            $table->index('xante_id');
            $table->index('hubspot_id');
            $table->index(['municipality', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
