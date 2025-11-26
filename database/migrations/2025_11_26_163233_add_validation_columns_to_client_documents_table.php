<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_documents', function (Blueprint $table) {
            // Add validation columns if they don't exist
            if (!Schema::hasColumn('client_documents', 'is_validated')) {
                $table->boolean('is_validated')->default(false);
            }
            if (!Schema::hasColumn('client_documents', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('client_documents', 'validated_at')) {
                $table->timestamp('validated_at')->nullable();
            }
            if (!Schema::hasColumn('client_documents', 'validation_notes')) {
                $table->text('validation_notes')->nullable();
            }
            
            // Add document_category if it doesn't exist (might be using 'category' instead)
            if (!Schema::hasColumn('client_documents', 'document_category')) {
                $table->string('document_category')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropColumn(['is_validated', 'validated_by', 'validated_at', 'validation_notes', 'document_category']);
        });
    }
};
