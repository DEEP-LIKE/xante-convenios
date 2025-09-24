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
            // Cambiar client_id por client_xante_id
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
            $table->string('client_xante_id')->nullable()->after('id');
            $table->foreign('client_xante_id')->references('xante_id')->on('clients');
            
            // Datos personales titular
            $table->string('name')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('curp', 18)->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('delivery_file')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('regime_type')->nullable();
            $table->string('occupation')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('additional_contact_phone')->nullable();
            $table->text('current_address')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('municipality')->nullable();
            $table->string('state')->nullable();
            
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
            
            // Checklist de documentos (JSON)
            $table->json('documents_checklist')->nullable();
            
            // Calculadora financiera (JSON)
            $table->json('financial_evaluation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            // Restaurar client_id
            $table->dropForeign(['client_xante_id']);
            $table->dropColumn('client_xante_id');
            $table->unsignedBigInteger('client_id')->after('id');
            $table->foreign('client_id')->references('id')->on('clients');
            
            // Eliminar todos los campos agregados
            $table->dropColumn([
                'name', 'birthdate', 'curp', 'rfc', 'email', 'phone',
                'delivery_file', 'civil_status', 'regime_type', 'occupation',
                'office_phone', 'additional_contact_phone', 'current_address',
                'neighborhood', 'postal_code', 'municipality', 'state',
                'spouse_name', 'spouse_birthdate', 'spouse_curp', 'spouse_rfc',
                'spouse_email', 'spouse_phone', 'spouse_delivery_file',
                'spouse_civil_status', 'spouse_regime_type', 'spouse_occupation',
                'spouse_office_phone', 'spouse_additional_contact_phone',
                'spouse_current_address', 'spouse_neighborhood', 'spouse_postal_code',
                'spouse_municipality', 'spouse_state', 'ac_name', 'ac_phone',
                'ac_quota', 'private_president_name', 'private_president_phone',
                'private_president_quota', 'documents_checklist', 'financial_evaluation'
            ]);
        });
    }
};
