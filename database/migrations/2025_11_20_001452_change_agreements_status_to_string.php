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
            // Cambiar columna status a string para soportar todos los estados
            $table->string('status')->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            // Revertir a enum (esto podría fallar si hay datos incompatibles, pero es lo correcto para down)
            $table->enum('status', [
                'draft',
                'documents_generating',
                'documents_generated',
                'documents_sent',
                'awaiting_client_docs',
                'documents_complete',
                'completed',
                'cancelled',
            ])->default('draft')->change();
        });
    }
};
