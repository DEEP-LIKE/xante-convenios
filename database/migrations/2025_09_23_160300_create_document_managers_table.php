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
        Schema::create('document_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            $table->string('document_type');
            $table->string('document_category');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('original_name')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->enum('upload_status', ['pending', 'uploading', 'uploaded', 'failed'])->default('pending');
            $table->enum('validation_status', ['pending', 'validating', 'valid', 'invalid', 'requires_review'])->default('pending');
            $table->json('validation_notes')->nullable();
            $table->json('extracted_data')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['agreement_id', 'document_type']);
            $table->index(['agreement_id', 'document_category']);
            $table->index('upload_status');
            $table->index('validation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_managers');
    }
};
