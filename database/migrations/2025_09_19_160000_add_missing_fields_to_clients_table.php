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
            // Verificar y agregar solo los campos que faltan
            if (!Schema::hasColumn('clients', 'current_address')) {
                $table->text('current_address')->nullable();
            }
            if (!Schema::hasColumn('clients', 'municipality')) {
                $table->string('municipality')->nullable();
            }
            if (!Schema::hasColumn('clients', 'state')) {
                $table->string('state')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['current_address', 'municipality', 'state']);
        });
    }
};
