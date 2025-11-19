<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            // Datos personales
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('curp', 18)->unique()->nullable();
            $table->string('rfc', 13)->unique()->nullable();
            
            // Datos adicionales
            $table->string('civil_status')->nullable();
            $table->string('regime_type')->nullable();
            $table->string('occupation')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('additional_contact_phone')->nullable();
            $table->string('delivery_file')->nullable();
            
            // Dirección
            $table->text('current_address')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('municipality')->nullable();
            $table->string('state')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('client_id');
            $table->index('curp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spouses');
    }
};
