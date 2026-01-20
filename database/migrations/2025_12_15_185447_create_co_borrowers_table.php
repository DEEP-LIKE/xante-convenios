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
        Schema::create('co_borrowers', function (Blueprint $table) {
            $table->id();

            // Datos personales
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('curp', 18)->nullable();
            $table->string('rfc', 13)->nullable();

            // Datos adicionales
            $table->string('civil_status')->nullable();
            $table->string('regime_type')->nullable(); // Régimen Fiscal
            $table->string('occupation')->nullable();
            $table->string('delivery_file')->nullable();

            // Dirección
            $table->text('current_address')->nullable();
            $table->string('house_number')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('municipality')->nullable();
            $table->string('state')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('co_borrowers');
    }
};
