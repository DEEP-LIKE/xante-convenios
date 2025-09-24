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
        Schema::table('clients', function (Blueprint $table) {
            // Solo agregar campos que NO existen en la migración original
            // Los campos birthdate, curp, rfc, current_address, municipality, state ya existen
            
            // Campo importante para el sistema wizard
            $table->string('xante_id')->unique()->nullable();
            
            // Datos personales titular adicionales
            $table->string('delivery_file')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('regime_type')->nullable();
            $table->string('occupation')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('additional_contact_phone')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('postal_code')->nullable();
            
            // Datos personales coacreditado/cónyuge
            $table->string('spouse_name')->nullable();
            $table->date('spouse_birthdate')->nullable();
            $table->string('spouse_curp', 18)->nullable();
            $table->string('spouse_rfc', 13)->nullable();
            $table->string('spouse_email')->nullable();
            $table->string('spouse_phone')->nullable();
            $table->string('spouse_delivery_file')->nullable();
            $table->string('spouse_civil_status')->nullable();
            $table->string('spouse_regime_type')->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->string('spouse_office_phone')->nullable();
            $table->string('spouse_additional_contact_phone')->nullable();
            $table->text('spouse_current_address')->nullable();
            $table->string('spouse_neighborhood')->nullable();
            $table->string('spouse_postal_code')->nullable();
            $table->string('spouse_municipality')->nullable();
            $table->string('spouse_state')->nullable();
            
            // Contacto AC y/o Presidente de Privada
            $table->string('ac_name')->nullable();
            $table->string('ac_phone')->nullable();
            $table->decimal('ac_quota', 10, 2)->nullable();
            $table->string('private_president_name')->nullable();
            $table->string('private_president_phone')->nullable();
            $table->decimal('private_president_quota', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Solo eliminar campos que agregamos (NO los que ya existían)
            $table->dropColumn([
                'xante_id',
                'delivery_file',
                'civil_status',
                'regime_type',
                'occupation',
                'office_phone',
                'additional_contact_phone',
                'neighborhood',
                'postal_code',
            ]);
            
            // Eliminar campos de datos personales coacreditado/cónyuge
            $table->dropColumn([
                'spouse_name',
                'spouse_birthdate',
                'spouse_curp',
                'spouse_rfc',
                'spouse_email',
                'spouse_phone',
                'spouse_delivery_file',
                'spouse_civil_status',
                'spouse_regime_type',
                'spouse_occupation',
                'spouse_office_phone',
                'spouse_additional_contact_phone',
                'spouse_current_address',
                'spouse_neighborhood',
                'spouse_postal_code',
                'spouse_municipality',
                'spouse_state',
            ]);
            
            // Eliminar campos de contacto AC y/o Presidente de Privada
            $table->dropColumn([
                'ac_name',
                'ac_phone',
                'ac_quota',
                'private_president_name',
                'private_president_phone',
                'private_president_quota',
            ]);
        });
    }
};
