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
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->string('document_name')->nullable()->after('document_type');
            $table->string('template_used')->nullable()->after('file_path');
            $table->timestamp('generated_at')->nullable()->after('template_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropColumn(['document_name', 'template_used', 'generated_at']);
        });
    }
};
