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
            // Agregar campos de número de domicilio
            $table->string('holder_house_number', 20)->nullable()->after('current_address');
            $table->string('spouse_house_number', 20)->nullable()->after('spouse_current_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn(['holder_house_number', 'spouse_house_number']);
        });
    }
};
